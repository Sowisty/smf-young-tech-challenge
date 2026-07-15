<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Invoice;
use App\Jobs\ProcessInvoiceOcr;
use OpenApi\Attributes as OA;
use Exception;

#[OA\Info(
    version: "1.2.0",
    title: "Young Tech Challenge - Dokumentacja API z Kolejkami, Auth i OCR (PDF/JPG/PNG)",
    description: "API do asynchronicznego przetwarzania faktur PDF, JPG i PNG w tle za pomocą kolejek systemowych, biblioteki Tesseract OCR i sztucznej inteligencji, w pełni zabezpieczone przez Laravel Sanctum."
)]
#[OA\Server(
    url: "http://127.0.0.1:8000/api",
    description: "Lokalny Serwer API"
)]
class InvoiceApiController extends Controller
{
    #[OA\Get(
        path: "/invoices",
        operationId: "getInvoicesList",
        summary: "Pobierz listę wszystkich zapisanych faktur",
        description: "Zwraca listę wszystkich faktur (w tym tych o statusie pending/processing) wraz z ich kontrahentami, pozycjami i płatnościami. Wymaga autoryzacji.",
        security: [["sanctum" => []]],
        tags: ["Faktury"]
    )]
    #[OA\Response(
        response: 200,
        description: "Pomyślnie pobrano listę faktur."
    )]
    public function index()
    {
        try {
            $invoices = Invoice::with(['contractor', 'items', 'payment'])->latest()->get();
            return response()->json($invoices, 200);
        } catch (Exception $e) {
            Log::error('Błąd pobierania listy faktur: ' . $e->getMessage());
            return response()->json(['error' => 'Nie udało się pobrać listy faktur.'], 500);
        }
    }

    #[OA\Get(
        path: "/invoices/{id}",
        operationId: "getInvoiceById",
        summary: "Pobierz szczegóły oraz status konkretnej faktury",
        description: "Zwraca pełne dane szczegółowe oraz aktualny status ('pending', 'processing', 'completed', 'failed') wybranej faktury. Służy do odpytywania (pollingu) o stan zadania w tle.",
        security: [["sanctum" => []]],
        tags: ["Faktury"]
    )]
    #[OA\Parameter(
        name: "id",
        description: "Identyfikator ID faktury",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Response(
        response: 200,
        description: "Pomyślnie pobrano szczegóły faktury."
    )]
    #[OA\Response(
        response: 404,
        description: "Faktura o podanym ID nie została znaleziona."
    )]
    public function show($id)
    {
        try {
            $invoice = Invoice::with(['contractor', 'items', 'payment'])->find($id);

            if (!$invoice) {
                return response()->json([
                    'error' => 'Faktura o podanym ID nie została znaleziona.'
                ], 404);
            }

            return response()->json($invoice, 200);
        } catch (Exception $e) {
            Log::error('Błąd pobierania szczegółów faktury: ' . $e->getMessage());
            return response()->json(['error' => 'Nie udało się pobrać szczegółów faktury.'], 500);
        }
    }

    #[OA\Post(
        path: "/invoices/upload",
        operationId: "uploadInvoice",
        summary: "Prześlij plik PDF, JPG lub PNG i zleć przetwarzanie w tle",
        description: "Zapisuje przesłany plik (PDF/JPG/JPEG/PNG) na dysku, tworzy rekord faktury ze statusem 'pending' i wrzuca zadanie do kolejki systemowej. Zwraca status 202 Accepted.",
        security: [["sanctum" => []]],
        tags: ["Faktury"]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                required: ["invoice_file"],
                properties: [
                    new OA\Property(
                        property: "invoice_file",
                        description: "Plik faktury w formacie PDF, JPG lub PNG (maks. 10MB)",
                        type: "string",
                        format: "binary"
                    )
                ]
            )
        )
    )]
    #[OA\Response(
        response: 202,
        description: "Plik przesłany pomyślnie, zadanie trafiło do kolejki w tle."
    )]
    public function upload(Request $request)
    {
        // Rozszerzamy walidację mimes o formaty graficzne: jpeg, png, jpg
        $request->validate([
            'invoice_file' => 'required|mimes:pdf,jpeg,png,jpg|max:10240',
        ]);

        try {
            $file = $request->file('invoice_file');

            // 1. Bezpieczne zapisanie pliku w katalogu storage/app/invoices
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('invoices', $fileName);

            // 2. Utworzenie rekordu faktury z domyślnym statusem 'pending'
            $invoice = Invoice::create([
                'number' => 'Przetwarzanie...',
                'date' => now()->format('Y-m-d'),
                'status' => 'pending',
                'file_path' => $filePath
            ]);

            // 3. Wrzucenie zadania przetwarzania w tle do Kolejki Laravela!
            ProcessInvoiceOcr::dispatch($invoice, $filePath);

            return response()->json([
                'message' => 'Plik został pomyślnie przesłany i dodany do kolejki przetwarzania w tle.',
                'invoice_id' => $invoice->id,
                'status' => $invoice->status
            ], 202);

        } catch (Exception $e) {
            Log::error('Błąd podczas inicjowania uploadu faktury: ' . $e->getMessage());
            return response()->json([
                'error' => 'Nie udało się zarejestrować faktury na serwerze.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Delete(
        path: "/invoices/{id}",
        operationId: "deleteInvoice",
        summary: "Usuń fakturę z bazy danych",
        description: "Usuwa wybraną fakturę oraz jej powiązany plik fizyczny z dysku.",
        security: [["sanctum" => []]],
        tags: ["Faktury"]
    )]
    #[OA\Response(
        response: 200,
        description: "Faktura pomyślnie usunięta."
    )]
    public function destroy($id)
    {
        try {
            $invoice = Invoice::find($id);

            if (!$invoice) {
                return response()->json([
                    'error' => 'Faktura o podanym ID nie została znaleziona.'
                ], 404);
            }

            // Usunięcie fizycznego pliku z pamięci, jeśli istnieje
            if ($invoice->file_path && Storage::exists($invoice->file_path)) {
                Storage::delete($invoice->file_path);
            }

            $invoice->delete();

            return response()->json([
                'message' => 'Faktura została pomyślnie usunięta z bazy danych.'
            ], 200);
        } catch (Exception $e) {
            Log::error('Błąd podczas usuwania faktury: ' . $e->getMessage());
            return response()->json(['error' => 'Nie udało się usunąć faktury.'], 500);
        }
    }
}