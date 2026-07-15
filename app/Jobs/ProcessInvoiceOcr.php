<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use App\Models\Invoice;
use App\Models\Contractor;
use App\Models\Item;
use App\Models\Payment;
use Exception;

class ProcessInvoiceOcr implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $invoice;
    protected $filePath;

    /**
     * Maksymalna liczba prób wykonania zadania w przypadku błędu.
     */
    public $tries = 3;

    /**
     * Czas (w sekundach) oczekiwania przed ponownym uruchomieniem zadania.
     */
    public $backoff = 5;

    /**
     * Stworzenie nowej instancji zadania w tle.
     */
    public function __construct(Invoice $invoice, string $filePath)
    {
        $this->invoice = $invoice;
        $this->filePath = $filePath;
    }

    /**
     * Wykonanie zadania – proces OCR (PDF lub Obraz) oraz analiza AI w tle.
     */
    public function handle()
    {
        Log::info("Uruchomiono przetwarzanie kolejki dla faktury ID: " . $this->invoice->id);

        $this->invoice->update([
            'status' => 'processing'
        ]);

        try {
            $absolutePath = Storage::path($this->filePath);

            if (!file_exists($absolutePath)) {
                throw new Exception("Plik faktury nie istnieje na dysku: " . $absolutePath);
            }

            // Sprawdzamy rozszerzenie pliku, aby dobrać odpowiednią metodę wyciągania tekstu
            $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
            $extractedText = '';

            if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                Log::info("Wykryto format graficzny ({$extension}). Uruchamianie systemowego OCR Tesseract...");
                
                // Wywołujemy Tesseract OCR w systemie Linux (wspiera polski i angielski)
                // escapeshellarg() chroni system przed próbami Command Injection
                $command = "tesseract " . escapeshellarg($absolutePath) . " stdout -l pol+eng 2>&1";
                $extractedText = shell_exec($command);

                if (empty(trim($extractedText)) || str_contains($extractedText, 'Error')) {
                    throw new Exception("Błąd przetwarzania obrazu przez Tesseract OCR: " . ($extractedText ?: "pusty wynik"));
                }
            } else {
                Log::info("Wykryto format PDF. Uruchamianie parsera PDF...");
                $parser = app(Parser::class);
                $pdf = $parser->parseFile($absolutePath);
                $extractedText = $pdf->getText();
            }

            if (empty(trim($extractedText))) {
                throw new Exception("Odczytany tekst z dokumentu jest pusty.");
            }

            // 2. Wysłanie danych do Groq AI
            $structuredData = $this->extractDataWithAi($extractedText);

            if (!$structuredData) {
                throw new Exception("Groq AI nie zwróciło poprawnej struktury JSON.");
            }

            // 3. Zapisanie wyekstrahowanych danych w transakcji bazodanowej SQLite
            DB::transaction(function () use ($structuredData) {
                // A. Kontrahent
                $contractorData = $structuredData['contractor'] ?? [];
                $contractor = Contractor::firstOrCreate(
                    ['nip' => $contractorData['nip'] ?? null],
                    [
                        'name' => $contractorData['name'] ?? 'Nieznany Kontrahent',
                        'address' => $contractorData['address'] ?? 'Brak adresu'
                    ]
                );

                // B. Aktualizacja faktury (pola numer, data i powiązanie z kontrahentem)
                $invoiceData = $structuredData['invoice'] ?? [];
                $this->invoice->update([
                    'number' => $invoiceData['number'] ?? 'Brak numeru',
                    'date' => $invoiceData['date'] ?? now()->format('Y-m-d'),
                    'contractor_id' => $contractor->id,
                    'status' => 'completed'
                ]);

                // C. Zapis pozycji
                $itemsData = $structuredData['items'] ?? [];
                foreach ($itemsData as $item) {
                    Item::create([
                        'invoice_id' => $this->invoice->id,
                        'name' => $item['name'] ?? 'Pozycja bez nazwy',
                        'quantity' => (int)($item['quantity'] ?? 1),
                        'price' => (float)($item['price'] ?? 0.00),
                        'total' => (float)($item['total'] ?? 0.00),
                    ]);
                }

                // D. Zapis płatności
                $paymentData = $structuredData['payment'] ?? [];
                Payment::create([
                    'invoice_id' => $this->invoice->id,
                    'amount' => (float)($paymentData['amount'] ?? 0.00),
                    'currency' => $paymentData['currency'] ?? 'PLN',
                    'method' => $paymentData['method'] ?? 'Przelew'
                ]);
            });

            Log::info("Pomyślnie przetworzono w tle fakturę ID: " . $this->invoice->id);

        } catch (Exception $e) {
            Log::error("Błąd w kolejce dla faktury ID {$this->invoice->id}: " . $e->getMessage());

            $this->invoice->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Komunikacja z Groq API.
     */
    private function extractDataWithAi(string $text): ?array
    {
        $apiKey = env('GROQ_API_KEY');

        if (!$apiKey) {
            Log::error('Brak GROQ_API_KEY w zadaniu kolejki!');
            return null;
        }

        $systemPrompt = "Jesteś asystentem księgowym. Twoim zadaniem jest dokładne przeanalizowanie tekstu odczytanego z faktury i wyciągnięcie z niego danych strukturalnych. " .
            "Odpowiedz wyłącznie poprawnym, płaskim obiektem JSON o podanej niżej strukturze. " .
            "Jeśli nie znajdziesz jakiegoś pola, wpisz null lub puste wartości. " .
            "Struktura JSON:" .
            "{" .
            "  \"contractor\": { \"name\": \"Pełna nazwa firmy/sprzedawcy\", \"address\": \"Pełny adres\", \"nip\": \"Sam numer NIP bez myślników\" }," .
            "  \"invoice\": { \"number\": \"Numer faktury\", \"date\": \"Data wystawienia w formacie RRRR-MM-DD\" }," .
            "  \"items\": [ { \"name\": \"Nazwa produktu/usługi\", \"quantity\": 1, \"price\": 10.00, \"total\": 10.00 } ]," .
            "  \"payment\": { \"amount\": 10.00, \"currency\": \"PLN\", \"method\": \"Metoda płatności (np. przelew, gotówka, karta)\" }" .
            "} " .
            "Bądź niezwykle dokładny. Zwróć wyłącznie czysty kod JSON. Nie dodawaj żadnych słów wstępu, komentarzy ani znaczników markdown.";

        try {
            $url = 'https://api.groq.com/openai/v1/chat/completions';

            $response = Http::retry(3, 1000)
                ->withToken($apiKey)
                ->post($url, [
                    'model' => 'llama-3.1-8b-instant',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $text]
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.1
                ]);

            if ($response->failed()) {
                Log::error('API Groq błąd w kolejce: ' . $response->status() . ' - ' . $response->body());
                return null;
            }

            $responseData = $response->json();
            $content = $responseData['choices'][0]['message']['content'] ?? null;

            if (!$content) {
                return null;
            }

            return json_decode($content, true);

        } catch (Exception $e) {
            Log::error('Wyjątek Groq w kolejce: ' . $e->getMessage());
            return null;
        }
    }
}