<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use App\Models\Invoice;
use App\Models\Contractor;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Smalot\PdfParser\Parser;
use Smalot\PdfParser\Document;

class InvoiceApiTest extends TestCase
{
    use RefreshDatabase; // Czyści bazę SQLite przed każdym pojedynczym testem, dbając o czystość danych

    protected $user;

    /**
     * Przygotowanie środowiska testowego przed każdym testem.
     * Automatycznie tworzymy użytkownika i logujemy go przez Sanctum.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Tworzymy użytkownika testowego w czystej bazie danych w pamięci RAM
        $this->user = User::create([
            'name' => 'Tester API',
            'email' => 'tester@example.com',
            'password' => bcrypt('password')
        ]);

        // Autoryzujemy każde zapytanie w tym pliku testowym jako zalogowany użytkownik
        Sanctum::actingAs($this->user);
    }

    /**
     * Test sprawdza, czy na samym początku lista faktur jest pusta.
     */
    public function test_can_retrieve_empty_invoice_list()
    {
        $response = $this->getJson('/api/invoices');

        $response->assertStatus(200);
        $response->assertJsonCount(0);
    }

    /**
     * Test sprawdza pobieranie listy, gdy w bazie znajdują się już faktury.
     */
    public function test_can_retrieve_invoices_list_with_data()
    {
        // Tworzymy testowego kontrahenta i fakturę bezpośrednio w bazie
        $contractor = Contractor::create([
            'name' => 'Testowy Contractor Sp. z o.o.',
            'address' => 'ul. Testowa 12, Warszawa',
            'nip' => '1234567890'
        ]);

        Invoice::create([
            'number' => 'FV/TEST/001',
            'date' => '2026-07-14',
            'contractor_id' => $contractor->id,
            'status' => 'completed'
        ]);

        $response = $this->getJson('/api/invoices');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.number', 'FV/TEST/001');
    }

    /**
     * Test sprawdza, czy usunięcie faktury usuwa ją fizycznie z bazy danych SQLite.
     */
    public function test_can_delete_invoice()
    {
        $contractor = Contractor::create([
            'name' => 'Firma do Usunięcia',
            'address' => 'ul. Czysta 5, Gdańsk',
            'nip' => '9876543210'
        ]);

        $invoice = Invoice::create([
            'number' => 'FV/TO-DELETE/2026',
            'date' => '2026-07-14',
            'contractor_id' => $contractor->id,
            'status' => 'completed'
        ]);

        // Wywołujemy endpoint DELETE
        $response = $this->deleteJson("/api/invoices/{$invoice->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Faktura została pomyślnie usunięta z bazy danych.'
        ]);

        // Upewniamy się, że rekord faktycznie zniknął z tabeli invoices
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
    }

    /**
     * Test sprawdza przesyłanie pliku oraz integrację z Groq AI w asynchronicznej kolejce.
     * Używamy sterownika "sync" dla potrzeb testu, dzięki czemu zadanie wykonuje się natychmiastowo.
     */
    public function test_uploading_and_parsing_invoice_using_mocked_ai()
    {
        // 1. Definiujemy "atrapę" odpowiedzi, jaką ma zwrócić Groq API
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'contractor' => [
                                    'name' => 'AI Mocked Seller Sp. z o.o.',
                                    'address' => 'ul. Algorytmiczna 8, Poznań',
                                    'nip' => '5556667788'
                                ],
                                'invoice' => [
                                    'number' => 'FV/AI-MOCK/999',
                                    'date' => '2026-07-14'
                                ],
                                'items' => [
                                    [
                                        'name' => 'Konsultacje sztucznej inteligencji',
                                        'quantity' => 2,
                                        'price' => 500.00,
                                        'total' => 1000.00
                                    ]
                                ],
                                'payment' => [
                                    'amount' => 1000.00,
                                    'currency' => 'PLN',
                                    'method' => 'Karta płatnicza'
                                ]
                            ])
                        ]
                    ]
                ]
            ], 200)
        ]);

        // 2. Mockujemy parser PDF (Smalot\PdfParser\Parser)
        $mockPdfDocument = Mockery::mock(Document::class);
        $mockPdfDocument->shouldReceive('getText')
            ->once()
            ->andReturn('FV/AI-MOCK/999 AI Mocked Seller Sp. z o.o. NIP 5556667788 Kwota: 1000 PLN');

        $mockPdfParser = Mockery::mock(Parser::class);
        $mockPdfParser->shouldReceive('parseFile')
            ->once()
            ->andReturn($mockPdfDocument);

        // Wstrzykujemy mocka parsera do kontenera usług Laravela
        $this->app->instance(Parser::class, $mockPdfParser);

        // 3. Generujemy atrapę pliku PDF na potrzeby walidacji HTTP Laravela
        $fakePdf = UploadedFile::fake()->create('mock_invoice.pdf', 150, 'application/pdf');

        // Wstrzykujemy wymagany klucz API w locie bezpośrednio do zmiennej środowiskowej procesu testowego PHPUnit
        putenv('GROQ_API_KEY=gsk_test_key_123');

        // Ustawiamy sterownik kolejki na "sync" dla środowiska testowego, aby zadanie wykonało się natychmiast w tym samym wątku
        config(['queue.default' => 'sync']);

        // 4. Wysyłamy żądanie POST pod nasz endpoint uploadu
        $response = $this->postJson('/api/invoices/upload', [
            'invoice_file' => $fakePdf
        ]);

        // 5. Sprawdzamy asercje
        // Oczekujemy statusu 202 Accepted, ponieważ upload natychmiast zwraca odpowiedź o przyjęciu zlecenia
        $response->assertStatus(202); 
        $response->assertJsonStructure([
            'message',
            'invoice_id',
            'status'
        ]);

        // Ponieważ zadanie wykonało się synchronicznie (sync), weryfikujemy czy dane z "atrapy" AI zostały poprawnie zapisane w bazie
        $this->assertDatabaseHas('contractors', ['nip' => '5556667788']);
        $this->assertDatabaseHas('invoices', ['number' => 'FV/AI-MOCK/999', 'status' => 'completed']);
        $this->assertDatabaseHas('items', ['name' => 'Konsultacje sztucznej inteligencji']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}