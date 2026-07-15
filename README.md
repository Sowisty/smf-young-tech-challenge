# 🧠 ParserAI - SaaS Invoice AI Reader

ParserAI to nowoczesny, bezpieczny i wysoce skalowalny system klasy SaaS służący do automatycznego odczytywania, rozpoznawania oraz strukturyzacji danych z faktur. Aplikacja wspiera dokumenty w formatach **PDF** oraz **obrazów (JPG, JPEG, PNG)**. 

Dzięki zastosowaniu hybrydowego podejścia do OCR, asynchronicznej kolejki zadań systemowych oraz modeli sztucznej inteligencji (LLM), system analizuje nieustrukturyzowany tekst i automatycznie mapuje go na czytelny format bazodanowy w ułamku sekundy.

---

## 🏗️ Opis Architektury Systemu

Projekt został zaprojektowany zgodnie z najlepszymi praktykami inżynierii wstecznej i zasadami SOLID (ze szczególnym uwzględnieniem Single Responsibility Principle). Architektura opiera się na separacji warstwy prezentacji, kontrolerów API, asynchronicznych procesorów zadań oraz bazy danych.

```text
               +-------------------------------------------+
               |         Frontend (Blade + Tailwind)       |
               +---------------------++--------------------+
                                     || Auth & REST Requests
                                     \/
               +-------------------------------------------+
               |        API Gateway (Laravel Sanctum)      |
               +---------------------++--------------------+
                                     ||
                                     \/
               +-------------------------------------------+
               |   InvoiceApiController (Slim Controller)  |
               +---------------------++--------------------+
                                     || Dispatches Job (202 Accepted)
                                     \/
               +-------------------------------------------+
               |        Database Queue (jobs table)        |
               +---------------------++--------------------+
                                     ||
                                     \/ (Daemon)
               +-------------------------------------------+
               |      Queue Worker (ProcessInvoiceOcr)     |
               +--+------------------------+------------+--+
                  |                        |            |
                  \/                       \/           \/
          [Smalot PDF Parser]     [Tesseract OCR]   [Groq AI Llama 3.1]
                  |                        |            |
                  +------------+-----------+------------+
                               |
                               \/ (Database Transaction)
               +-------------------------------------------+
               |             SQLite Database               |
               +-------------------------------------------+
```

### 1. Warstwa Prezentacji (Frontend)
* **Technologia:** Laravel Blade, Tailwind CSS, FontAwesome.
* **Zarządzanie Stanem:** Bezstanowa komunikacja z API. Token autoryzacyjny (Bearer Token) po zalogowaniu jest bezpiecznie zapisywany w `localStorage` przeglądarki.
* **Asynchroniczny Polling:** Po przesłaniu pliku frontend natychmiast otrzymuje status `202 Accepted` wraz z ID faktury. Skrypt JavaScript automatycznie odpytuje (polluje) endpoint `/api/invoices/{id}` co 2 sekundy, dynamicznie aktualizując stan interfejsu (Status: *W kolejce* -> *Analiza AI...* -> *Ukończono/Błąd*) bez blokowania pracy użytkownika.

### 2. Warstwa Autoryzacji (Laravel Sanctum)
* Bezpieczeństwo endpointów API jest kontrolowane przy użyciu bezstanowych tokenów API (Personal Access Tokens).
* Metody rejestracji, logowania oraz unieważniania sesji (wylogowania) obsługiwane są przez dedykowany `AuthController`.

### 3. Lekkie Kontrolery (Slim Controllers)
* `InvoiceApiController` odpowiada wyłącznie za przyjmowanie żądań, walidację formatów plików (dozwolone: `pdf, jpeg, png, jpg` do 10MB), zapisanie pliku na dysku i natychmiastowe wrzucenie zadania do kolejki systemowej. Dzięki temu czas odpowiedzi serwera na upload pliku wynosi mniej niż 100ms.

### 4. Asynchroniczny Procesor Tła (Laravel Queues & Jobs)
* Cała ciężka logika biznesowa została wydzielona do klasy zadania `ProcessInvoiceOcr`.
* Zadania są kolejkowane w bazie danych (tabela `jobs`). 
* **Silnik OCR:**
    * Dla plików **PDF**: Wykorzystywany jest natywny parser biblioteczny PHP `Smalot\PdfParser`.
    * Dla plików **graficznych (JPG/PNG)**: Wywoływany jest systemowy, wielojęzyczny silnik **Tesseract OCR** z polskim oraz angielskim słownikiem (`pol+eng`).
* **Integracja AI:** Wyekstrahowany tekst trafia do API Groq (`llama-3.1-8b-instant`) z restrykcyjnym promptem systemowym, wymuszającym strukturę JSON.
* **Transakcyjność bazodanowa:** Zapis wyekstrahowanych danych do tabel `contractors`, `invoices`, `items` oraz `payments` odbywa się wewnątrz bezpiecznej transakcji SQL (`DB::transaction`). W przypadku błędu na którymkolwiek etapie, baza danych jest w pełni wycofywana do stanu stabilnego (Rollback), a faktura otrzymuje status `failed`.

### 5. Struktura Bazy Danych (Relacyjna SQLite)
* `contractors` (Kontrahenci): Unikalność identyfikowana po numerze NIP.
* `invoices` (Faktury): Przechowuje numer, datę, ścieżkę fizyczną do pliku oraz statusy przetwarzania.
* `items` (Pozycje): Powiązane relacją jeden-do-wielu (`hasMany`) z fakturą.
* `payments` (Płatności): Relacja jeden-do-jednego (`hasOne`) z kwotą i metodą płatności.

---

## 🛠️ Instrukcja Uruchomienia Projektu (Docker)

Najprostszym i zalecanym sposobem na uruchomienie projektu w identycznym środowisku produkcyjnym jest użycie dołączonej konfiguracji **Docker**. Kontener automatycznie skonfiguruje PHP 8.4-fpm, serwer Nginx oraz doinstaluje silnik Tesseract OCR.

### Wymagania wstępne:
* Zainstalowany i uruchomiony **Docker Desktop** na Twoim komputerze.
* Klucz API do platformy **Groq** (`gsk_...`).

### Krok 1: Sklonowanie repozytorium i przygotowanie `.env`
1. Sklonuj repozytorium i wejdź do katalogu projektu:
   ```bash
   git clone https://github.com/Sowisty/smf-young-tech-challenge.git
   cd smf-young-tech-challenge
   ```
2. Skopiuj szablon pliku środowiskowego:
   ```bash
   cp .env.example .env
   ```
3. Otwórz plik `.env` i uzupełnij klucz API Groq oraz zmień połączenia na SQLite i kolejki bazodanowe:
   ```env
   DB_DATABASE=/var/www/database/database.sqlite

   GROQ_API_KEY=twój_klucz_gsk_...
   ```

### Krok 2: Budowanie i uruchomienie kontenerów
Uruchom budowanie obrazów systemowych w tle:
```bash
docker-compose up -d --build
```
*Docker pobierze niezbędne warstwy, skompiluje PHP 8.4, zainstaluje Composera, zależności biblioteczne oraz silnik Tesseract OCR wraz z polskimi paczkami językowymi.*

### Krok 3: Konfiguracja aplikacji i migracja bazy
Wykonaj migracje bazodanowe i zainstaluj mechanizmy API wewnątrz uruchomionego kontenera:
```bash
# 1. Generowanie klucza aplikacji
docker-compose exec app php artisan key:generate

# 2. Instalacja i konfiguracja tabel Sanctum (API Tokeny)
docker-compose exec app php artisan install:api

# 3. Uruchomienie migracji struktur tabel w bazie SQLite
docker-compose exec app php artisan migrate:fresh

# 4. Zoptymalizowanie i wygenerowanie klas autoloadera
docker-compose exec app composer dump-autoload --optimize

# 5. Nadanie odpowiednich uprawnień do zapisu bazy i storage przez serwer Nginx
docker-compose exec app chown -R www-data:www-data /var/www/storage /var/www/database
```

### Krok 4: Uruchomienie procesora zadań w tle (Queue Worker)
Aby system mógł przetwarzać dokumenty w tle, musisz uruchomić Workera kolejki. Otwórz nowy, osobny terminal w katalogu projektu i wpisz:
```bash
docker-compose exec app php artisan queue:work
```
*Zostaw to okno terminala otwarte. Będziesz w nim na bieżąco widział statusy wykonywania OCR i zapytań do AI.*

### Krok 5: Dostęp do aplikacji
Otwórz przeglądarkę i wejdź pod adres:
👉 **http://127.0.0.1:8000**

---

## 🧪 Testy Automatyczne

Projekt posiada pełne pokrycie testami jednostkowymi i integracyjnymi (Feature Tests), sprawdzającymi autoryzację tokenów Sanctum, asynchroniczny upload (status 202), poprawność usuwania oraz symulowanie (mockowanie) odpowiedzi z platformy Groq AI.

Aby uruchomić zestaw testów wewnątrz kontenera Docker, wykonaj polecenie:
```bash
docker-compose exec app php artisan test
```

Wszystkie testy zostaną uruchomione w odizolowanej bazie danych SQLite w pamięci RAM (`:memory:`), co gwarantuje szybkość wykonania i brak modyfikacji danych deweloperskich.

---

## 📝 Licencja
Projekt jest dostępny na licencji [MIT](LICENSE).
