<!DOCTYPE html>
<html lang="pl" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SaaS Invoice AI Reader - Panel Główny</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>
<body class="h-full flex flex-col">

    <!-- Auth Guard Screens -->
    <div id="auth-screen" class="fixed inset-0 bg-slate-900 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-8 shadow-2xl space-y-6">
            <div class="text-center space-y-2">
                <div class="inline-flex bg-indigo-50 text-indigo-600 p-4 rounded-2xl">
                    <i class="fa-solid fa-user-shield text-3xl"></i>
                </div>
                <h2 class="text-2xl font-extrabold text-slate-800">Dostęp zabezpieczony</h2>
                <p class="text-xs text-slate-400">Zaloguj się lub zarejestruj konto, aby zarządzać fakturami.</p>
            </div>

            <!-- Tabs -->
            <div class="flex border-b border-slate-100">
                <button onclick="toggleAuthTab('login')" id="tab-login" class="flex-1 pb-3 text-sm font-bold text-indigo-600 border-b-2 border-indigo-600 transition">Logowanie</button>
                <button onclick="toggleAuthTab('register')" id="tab-register" class="flex-1 pb-3 text-sm font-semibold text-slate-400 border-b-2 border-transparent hover:text-slate-600 transition">Rejestracja</button>
            </div>

            <!-- Login Form -->
            <form id="login-form" class="space-y-4" autocomplete="off">
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-500 uppercase">E-mail</label>
                    <input type="email" id="login-email" required class="w-full p-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none text-sm transition" placeholder="admin@example.com" />
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-500 uppercase">Hasło</label>
                    <input type="password" id="login-password" required class="w-full p-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none text-sm transition" placeholder="••••••••" />
                </div>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-xl shadow-lg shadow-indigo-100 transition">Zaloguj się</button>
            </form>

            <!-- Register Form -->
            <form id="register-form" class="space-y-4 hidden" autocomplete="off">
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-500 uppercase">Imię i nazwisko</label>
                    <input type="text" id="reg-name" required class="w-full p-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none text-sm transition" placeholder="Jan Kowalski" />
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-500 uppercase">E-mail</label>
                    <input type="email" id="reg-email" required class="w-full p-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none text-sm transition" placeholder="jan@kowalski.pl" />
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-500 uppercase">Hasło</label>
                    <input type="password" id="reg-password" required class="w-full p-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none text-sm transition" placeholder="Min. 6 znaków" />
                </div>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-xl shadow-lg shadow-indigo-100 transition">Zarejestruj konto</button>
            </form>

            <div id="auth-error" class="hidden p-3 bg-rose-50 text-rose-600 text-xs font-semibold rounded-xl text-center"></div>
        </div>
    </div>

    <!-- Top Navigation -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center space-x-3">
                    <div class="bg-indigo-600 text-white p-2.5 rounded-xl shadow-lg shadow-indigo-200">
                        <i class="fa-solid fa-brain text-xl"></i>
                    </div>
                    <span class="text-xl font-bold text-slate-800 tracking-tight">Parser<span class="text-indigo-600">AI</span></span>
                </div>
                <div class="flex items-center space-x-4">
                    <span id="user-display" class="text-sm font-semibold text-slate-600"></span>
                    <button onclick="handleLogout()" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition">
                        <i class="fa-solid fa-power-off"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Grid -->
    <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8 space-y-8">
        
        <!-- Stats Row -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center space-x-4">
                <div class="p-4 bg-indigo-50 text-indigo-600 rounded-xl">
                    <i class="fa-solid fa-file-invoice text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-400">Przetworzone Faktury</p>
                    <h3 class="text-2xl font-bold text-slate-800" id="stat-count">0</h3>
                </div>
            </div>
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center space-x-4">
                <div class="p-4 bg-emerald-50 text-emerald-600 rounded-xl">
                    <i class="fa-solid fa-wallet text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-400">Łączna Kwota (PLN)</p>
                    <h3 class="text-2xl font-bold text-slate-800" id="stat-total">0.00 PLN</h3>
                </div>
            </div>
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center space-x-4">
                <div class="p-4 bg-amber-50 text-amber-600 rounded-xl">
                    <i class="fa-solid fa-building text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-400">Zarejestrowani Kontrahenci</p>
                    <h3 class="text-2xl font-bold text-slate-800" id="stat-total-contractors">0</h3>
                </div>
            </div>
        </div>

        <!-- Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- Left Panel: Dropzone -->
            <div class="lg:col-span-5 space-y-6">
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-800 mb-2">Prześlij plik faktury</h2>
                    <p class="text-sm text-slate-400 mb-6">Wybierz lub przeciągnij dokument. Obsługujemy formaty PDF, JPG oraz PNG. Parser automatycznie odczyta tekst i uporządkuje go za pomocą AI.</p>

                    <form id="upload-form" class="space-y-4">
                        <!-- Zmiana accept, aby przeglądarka pozwalała wybrać PDF oraz obrazy -->
                        <div id="dropzone" class="border-2 border-dashed border-slate-200 hover:border-indigo-500 transition duration-300 rounded-2xl p-8 flex flex-col items-center justify-center cursor-pointer bg-slate-50/50 hover:bg-indigo-50/10">
                            <input type="file" id="file-input" name="invoice_file" accept=".pdf,.jpg,.jpeg,.png" class="hidden" />
                            <div class="p-4 bg-indigo-50 text-indigo-600 rounded-full mb-4">
                                <i class="fa-solid fa-cloud-arrow-up text-3xl"></i>
                            </div>
                            <span class="text-sm font-semibold text-slate-700">Przeciągnij plik PDF, JPG lub PNG tutaj</span>
                            <span class="text-xs text-slate-400 mt-1">lub kliknij, aby wybrać z dysku (PDF, JPG, PNG)</span>
                            <span class="text-[10px] text-slate-400 mt-4" id="selected-file-name">Maksymalny rozmiar: 10MB</span>
                        </div>

                        <button type="submit" id="submit-btn" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-4 rounded-xl shadow-lg shadow-indigo-100 transition duration-300 flex items-center justify-center space-x-2">
                            <i class="fa-solid fa-microchip"></i>
                            <span>Uruchom analizę w tle (Kolejka)</span>
                        </button>
                    </form>
                </div>

                <div id="status-box" class="hidden p-4 rounded-2xl flex items-start space-x-3 transition duration-300">
                    <div id="status-icon" class="text-lg"></div>
                    <div class="flex-1 text-sm font-medium" id="status-message"></div>
                </div>
            </div>

            <!-- Right Panel: List of Invoices -->
            <div class="lg:col-span-7 space-y-6">
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col h-full">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-bold text-slate-800">Moje faktury</h2>
                        <button onclick="loadInvoices()" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-50 rounded-xl transition">
                            <i class="fa-solid fa-rotate"></i>
                        </button>
                    </div>

                    <div class="overflow-y-auto max-h-[500px] pr-2 space-y-3" id="invoices-list"></div>
                </div>
            </div>
        </div>
    </main>

    <!-- Details Modal -->
    <div id="details-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 transition-all duration-300">
        <div class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto relative flex flex-col">
            <button onclick="closeModal()" class="absolute top-6 right-6 p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-50 rounded-xl transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
            
            <div class="flex items-center space-x-3 mb-6">
                <div class="bg-indigo-50 text-indigo-600 p-2.5 rounded-xl">
                    <i class="fa-solid fa-receipt text-xl"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-slate-800" id="modal-invoice-number">Faktura</h3>
                    <p class="text-xs text-slate-400" id="modal-invoice-date">Wystawiono: --</p>
                </div>
            </div>

            <div class="space-y-6 flex-1">
                <div class="bg-slate-50 p-5 rounded-2xl border border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <span class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Odbiorca (Kontrahent)</span>
                        <h4 class="font-bold text-slate-700 mt-1" id="modal-contractor-name">--</h4>
                        <p class="text-xs text-slate-500 mt-1" id="modal-contractor-address">--</p>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Identyfikator NIP</span>
                        <p class="text-sm font-semibold text-slate-700 mt-1" id="modal-contractor-nip">--</p>
                    </div>
                </div>

                <div>
                    <h4 class="text-sm font-bold text-slate-800 mb-3">Pozycje faktury</h4>
                    <div class="overflow-hidden border border-slate-100 rounded-xl">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100">
                                    <th class="p-3 text-xs font-bold text-slate-500">Nazwa</th>
                                    <th class="p-3 text-xs font-bold text-slate-500 text-center">Ilość</th>
                                    <th class="p-3 text-xs font-bold text-slate-500 text-right">Cena</th>
                                    <th class="p-3 text-xs font-bold text-slate-500 text-right">Suma</th>
                                </tr>
                            </thead>
                            <tbody id="modal-items-tbody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-indigo-50/50 p-5 rounded-2xl border border-indigo-100/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <span class="text-[10px] uppercase tracking-wider font-bold text-indigo-400">Płatność</span>
                        <p class="text-xs text-slate-500 mt-1">Metoda: <span class="font-bold text-slate-700" id="modal-payment-method">--</span></p>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] uppercase tracking-wider font-bold text-indigo-400">Do zapłaty</span>
                        <h3 class="text-2xl font-black text-indigo-600 mt-1" id="modal-payment-amount">0.00 PLN</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- UI Logic Script with Sanctum Integration & Queue Polling -->
    <script>
        const authScreen = document.getElementById('auth-screen');
        const loginForm = document.getElementById('login-form');
        const registerForm = document.getElementById('register-form');
        const authError = document.getElementById('auth-error');
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('file-input');
        const uploadForm = document.getElementById('upload-form');
        const submitBtn = document.getElementById('submit-btn');
        const selectedFileName = document.getElementById('selected-file-name');
        const statusBox = document.getElementById('status-box');
        const statusMessage = document.getElementById('status-message');
        const statusIcon = document.getElementById('status-icon');
        const invoicesList = document.getElementById('invoices-list');

        let allInvoicesData = [];
        let pollingIntervals = {}; // Śledzenie interwałów pollingu po ID faktury

        // Token Sanctum Storage
        function getAuthToken() { return localStorage.getItem('access_token'); }
        function setAuthToken(token) { localStorage.setItem('access_token', token); }
        function removeAuthToken() { localStorage.removeItem('access_token'); }

        function checkAuth() {
            // 1. Resetowanie formularzy na starcie (czyści stare wpisy przeglądarki)
            loginForm.reset();
            registerForm.reset();

            // 2. Reszta Twojego dotychczasowego kodu checkAuth...
            const token = getAuthToken();
            const user = localStorage.getItem('user_name');
            if (token) {
                authScreen.classList.add('hidden');
                document.getElementById('user-display').textContent = `Witaj, ${user}`;
                loadInvoices();
            } else {
                authScreen.classList.remove('hidden');
            }
        }

        function toggleAuthTab(tab) {
            authError.classList.add('hidden');
            if (tab === 'login') {
                document.getElementById('tab-login').className = "flex-1 pb-3 text-sm font-bold text-indigo-600 border-b-2 border-indigo-600 transition";
                document.getElementById('tab-register').className = "flex-1 pb-3 text-sm font-semibold text-slate-400 border-b-2 border-transparent hover:text-slate-600 transition";
                loginForm.classList.remove('hidden');
                registerForm.classList.add('hidden');
            } else {
                document.getElementById('tab-register').className = "flex-1 pb-3 text-sm font-bold text-indigo-600 border-b-2 border-indigo-600 transition";
                document.getElementById('tab-login').className = "flex-1 pb-3 text-sm font-semibold text-slate-400 border-b-2 border-transparent hover:text-slate-600 transition";
                registerForm.classList.remove('hidden');
                loginForm.classList.add('hidden');
            }
        }

        // Login Submit
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            authError.classList.add('hidden');
            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;

            try {
                const res = await fetch('/api/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ email, password })
                });
                const data = await res.json();
                if (res.ok) {
                    setAuthToken(data.access_token);
                    localStorage.setItem('user_name', data.user.name);
                    checkAuth();
                } else {
                    authError.textContent = data.error || "Błąd logowania.";
                    authError.classList.remove('hidden');
                }
            } catch {
                authError.textContent = "Brak połączenia z API.";
                authError.classList.remove('hidden');
            }
        });

        // Register Submit
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            authError.classList.add('hidden');
            const name = document.getElementById('reg-name').value;
            const email = document.getElementById('reg-email').value;
            const password = document.getElementById('reg-password').value;

            try {
                const res = await fetch('/api/register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ name, email, password })
                });
                const data = await res.json();
                if (res.ok) {
                    setAuthToken(data.access_token);
                    localStorage.setItem('user_name', data.user.name);
                    checkAuth();
                } else {
                    authError.textContent = data.errors ? Object.values(data.errors)[0] : (data.error || "Błąd rejestracji.");
                    authError.classList.remove('hidden');
                }
            } catch {
                authError.textContent = "Brak połączenia z API.";
                authError.classList.remove('hidden');
            }
        });

        async function handleLogout() {
            try {
                await fetch('/api/logout', {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${getAuthToken()}`, 'Accept': 'application/json' }
                });
            } catch {}
            removeAuthToken();
            localStorage.removeItem('user_name');
            authScreen.classList.remove('hidden');
            invoicesList.innerHTML = '';
        }

        // Drag & Drop
        dropzone.addEventListener('click', () => fileInput.click());
        dropzone.addEventListener('dragover', (e) => { e.preventDefault(); dropzone.classList.add('border-indigo-500', 'bg-indigo-50/10'); });
        dropzone.addEventListener('dragleave', () => { dropzone.classList.remove('border-indigo-500', 'bg-indigo-50/10'); });
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('border-indigo-500', 'bg-indigo-50/10');
            if (e.dataTransfer.files.length) { fileInput.files = e.dataTransfer.files; updateFileLabel(); }
        });
        fileInput.addEventListener('change', updateFileLabel);

        function updateFileLabel() {
            if (fileInput.files.length) {
                selectedFileName.textContent = `Wybrany plik: ${fileInput.files[0].name}`;
                selectedFileName.classList.add('text-indigo-600', 'font-medium');
            } else {
                selectedFileName.textContent = "Maksymalny rozmiar: 10MB";
                selectedFileName.classList.remove('text-indigo-600', 'font-medium');
            }
        }

        // Upload Form with Async Handling
        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!fileInput.files.length) { showStatus("Proszę najpierw wybrać plik PDF, JPG lub PNG.", "warning"); return; }

            const formData = new FormData();
            formData.append('invoice_file', fileInput.files[0]);

            submitBtn.disabled = true;
            submitBtn.innerHTML = `<i class="fa-solid fa-spinner animate-spin"></i> <span>Wysyłanie na serwer...</span>`;

            try {
                const response = await fetch('/api/invoices/upload', {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${getAuthToken()}`, 'Accept': 'application/json' },
                    body: formData
                });

                const result = await response.json();

                if (response.status === 202) {
                    showStatus("Faktura przyjęta do kolejki w tle. Trwa odczyt i analiza AI...", "info");
                    uploadForm.reset();
                    updateFileLabel();
                    loadInvoices(); // Odśwież listę, aby zobaczyć pending fakturę
                    startStatusPolling(result.invoice_id); // Rozpocznij odpytywanie o tę konkretną fakturę
                } else {
                    showStatus(result.error || "Wystąpił błąd przy przesyłaniu pliku.", "error");
                }
            } catch (error) {
                showStatus("Błąd połączenia z serwerem API.", "error");
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = `<i class="fa-solid fa-microchip"></i> <span>Uruchom analizę w tle (Kolejka)</span>`;
            }
        });

        // Polling statusu faktury
        function startStatusPolling(id) {
            if (pollingIntervals[id]) clearInterval(pollingIntervals[id]);

            pollingIntervals[id] = setInterval(async () => {
                try {
                    const response = await fetch(`/api/invoices/${id}`, {
                        headers: { 'Authorization': `Bearer ${getAuthToken()}`, 'Accept': 'application/json' }
                    });
                    const data = await response.json();

                    if (response.ok) {
                        // Aktualizacja statusu w lokalnym zbiorze danych i odświeżenie listy
                        const index = allInvoicesData.findIndex(inv => inv.id === id);
                        if (index !== -1) {
                            allInvoicesData[index] = data;
                            renderInvoices(allInvoicesData);
                            updateStats(allInvoicesData);
                        } else {
                            loadInvoices();
                        }

                        // Jeśli przetwarzanie zostało zakończone lub uległo awarii, wyłączamy odpytywanie
                        if (data.status === 'completed') {
                            clearInterval(pollingIntervals[id]);
                            delete pollingIntervals[id];
                            showStatus(`Faktura nr ${data.number} została pomyślnie sparsowana przez AI!`, "success");
                        } else if (data.status === 'failed') {
                            clearInterval(pollingIntervals[id]);
                            delete pollingIntervals[id];
                            showStatus(`Nie udało się sparsować faktury. Błąd: ${data.error_message || 'Nieznany'}`, "error");
                        }
                    }
                } catch {
                    clearInterval(pollingIntervals[id]);
                    delete pollingIntervals[id];
                }
            }, 2000); // Odpytuj o status co 2 sekundy
        }

        // Load Invoices List
        async function loadInvoices() {
            try {
                const response = await fetch('/api/invoices', {
                    headers: { 'Authorization': `Bearer ${getAuthToken()}`, 'Accept': 'application/json' }
                });

                // JEŚLI SERWER ODPOWIADA 401 (TOKEN NIEAKTYWNY) -> WYLOGUJ AUTOMATYCZNIE
                if (response.status === 401) {
                    forceLogoutDueToExpiredSession();
                    return;
                }

                const data = await response.json();
                
                if (response.ok) {
                    allInvoicesData = data;
                    renderInvoices(data);
                    updateStats(data);

                    data.forEach(inv => {
                        if ((inv.status === 'pending' || inv.status === 'processing') && !pollingIntervals[inv.id]) {
                            startStatusPolling(inv.id);
                        }
                    });
                }
            } catch (error) {
                invoicesList.innerHTML = `<div class="text-center py-8 text-rose-500"><i class="fa-solid fa-triangle-exclamation text-2xl mb-2"></i><p class="text-sm">Nie udało się pobrać listy.</p></div>`;
            }
        }

        function renderInvoices(invoices) {
            if (invoices.length === 0) {
                invoicesList.innerHTML = `
                    <div class="text-center py-16 text-slate-400">
                        <i class="fa-solid fa-folder-open text-4xl mb-3 text-slate-300"></i>
                        <p class="text-sm">Brak przetworzonych faktur w bazie danych.</p>
                    </div>`;
                return;
            }

            invoicesList.innerHTML = invoices.map(inv => {
                let statusBadge = '';
                let amountDisplay = '';
                let clickAction = `onclick="openDetails(${inv.id})"`;

                if (inv.status === 'pending') {
                    statusBadge = `<span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-600/20"><i class="fa-solid fa-hourglass-start animate-pulse mr-1"></i> W kolejce</span>`;
                    amountDisplay = `<span class="text-xs text-slate-400">Oczekiwanie...</span>`;
                    clickAction = `style="cursor: default;"`;
                } else if (inv.status === 'processing') {
                    statusBadge = `<span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-800 ring-1 ring-inset ring-indigo-600/20"><i class="fa-solid fa-gears animate-spin mr-1"></i> Analiza AI...</span>`;
                    amountDisplay = `<span class="text-xs text-slate-400">Przetwarzanie...</span>`;
                    clickAction = `style="cursor: default;"`;
                } else if (inv.status === 'failed') {
                    statusBadge = `<span class="inline-flex items-center rounded-md bg-rose-50 px-2 py-1 text-xs font-medium text-rose-800 ring-1 ring-inset ring-rose-600/20"><i class="fa-solid fa-circle-xmark mr-1"></i> Błąd</span>`;
                    amountDisplay = `<span class="text-xs text-rose-500 font-bold">Failed</span>`;
                    clickAction = `onclick="showStatus('Błąd: ${inv.error_message}', 'error')"`;
                } else {
                    // Status 'completed'
                    amountDisplay = `<span class="text-sm font-bold text-indigo-600 bg-indigo-50/50 px-3 py-1.5 rounded-xl">${parseFloat(inv.payment?.amount || 0).toFixed(2)} ${inv.payment?.currency || 'PLN'}</span>`;
                }

                return `
                    <div class="p-4 bg-white border border-slate-100 rounded-2xl hover:border-indigo-100 shadow-sm hover:shadow-md hover:shadow-indigo-50/50 flex justify-between items-center transition group">
                        <div class="flex items-center space-x-3 cursor-pointer" ${clickAction}>
                            <div class="p-3 bg-slate-50 text-slate-500 group-hover:bg-indigo-50 group-hover:text-indigo-600 rounded-xl transition">
                                <i class="fa-solid fa-receipt text-lg"></i>
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h4 class="font-bold text-slate-800 text-sm">FV: ${inv.number}</h4>
                                    ${statusBadge}
                                </div>
                                <p class="text-xs text-slate-400 font-medium">${inv.contractor?.name || 'Oczekiwanie na dane...'} • ${inv.date || '--'}</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            ${amountDisplay}
                            <button onclick="deleteInvoice(${inv.id})" class="p-2 text-slate-300 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function updateStats(invoices) {
            const completedInvoices = invoices.filter(inv => inv.status === 'completed');
            document.getElementById('stat-count').textContent = completedInvoices.length;
            
            const total = completedInvoices.reduce((sum, inv) => sum + parseFloat(inv.payment?.amount || 0), 0);
            document.getElementById('stat-total').textContent = `${total.toFixed(2)} PLN`;

            const contractors = new Set(completedInvoices.map(inv => inv.contractor?.nip).filter(nip => nip));
            document.getElementById('stat-total-contractors').textContent = contractors.size;
        }

        async function deleteInvoice(id) {
            if (!confirm("Czy na pewno chcesz usunąć tę fakturę z bazy danych SQLite?")) return;

            try {
                const response = await fetch(`/api/invoices/${id}`, {
                    method: 'DELETE',
                    headers: { 'Authorization': `Bearer ${getAuthToken()}`, 'Accept': 'application/json' }
                });

                if (response.ok) {
                    showStatus("Faktura została pomyślnie usunięta.", "success");
                    if (pollingIntervals[id]) {
                        clearInterval(pollingIntervals[id]);
                        delete pollingIntervals[id];
                    }
                    loadInvoices();
                } else {
                    showStatus("Nie udało się usunąć faktury.", "error");
                }
            } catch (error) {
                showStatus("Błąd połączenia z serwerem.", "error");
            }
        }

        function openDetails(id) {
            const invoice = allInvoicesData.find(inv => inv.id === id);
            if (!invoice || invoice.status !== 'completed') return;

            document.getElementById('modal-invoice-number').textContent = `Faktura VAT nr ${invoice.number}`;
            document.getElementById('modal-invoice-date').textContent = `Wystawiono: ${invoice.date || '--'}`;
            document.getElementById('modal-contractor-name').textContent = invoice.contractor?.name || 'Brak danych';
            document.getElementById('modal-contractor-address').textContent = invoice.contractor?.address || 'Brak adresu';
            document.getElementById('modal-contractor-nip').textContent = invoice.contractor?.nip || 'Brak NIP';
            document.getElementById('modal-payment-method').textContent = invoice.payment?.method || 'Nie określono';
            document.getElementById('modal-payment-amount').textContent = `${parseFloat(invoice.payment?.amount || 0).toFixed(2)} ${invoice.payment?.currency || 'PLN'}`;

            const tbody = document.getElementById('modal-items-tbody');
            if (invoice.items && invoice.items.length) {
                tbody.innerHTML = invoice.items.map(item => `
                    <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50/50 transition">
                        <td class="p-3 text-xs text-slate-700 font-semibold">${item.name}</td>
                        <td class="p-3 text-xs text-slate-500 text-center font-bold">${item.quantity}</td>
                        <td class="p-3 text-xs text-slate-500 text-right font-medium">${parseFloat(item.price).toFixed(2)}</td>
                        <td class="p-3 text-xs text-slate-800 text-right font-bold">${parseFloat(item.total).toFixed(2)}</td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = `<tr><td colspan="4" class="p-4 text-center text-xs text-slate-400">Brak pozycji na tej fakturze</td></tr>`;
            }

            document.getElementById('details-modal').classList.remove('hidden');
        }

        function closeModal() { document.getElementById('details-modal').classList.add('hidden'); }

        function showStatus(message, type) {
            statusBox.classList.remove('hidden', 'bg-amber-50', 'text-amber-700', 'bg-indigo-50', 'text-indigo-700', 'bg-emerald-50', 'text-emerald-700', 'bg-rose-50', 'text-rose-700');
            if (type === "warning") { statusBox.classList.add('bg-amber-50', 'text-amber-700'); statusIcon.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i>`; }
            else if (type === "info") { statusBox.classList.add('bg-indigo-50', 'text-indigo-700'); statusIcon.innerHTML = `<i class="fa-solid fa-circle-info animate-pulse"></i>`; }
            else if (type === "success") { statusBox.classList.add('bg-emerald-50', 'text-emerald-700'); statusIcon.innerHTML = `<i class="fa-solid fa-circle-check"></i>`; }
            else if (type === "error") { statusBox.classList.add('bg-rose-50', 'text-rose-700'); statusIcon.innerHTML = `<i class="fa-solid fa-circle-xmark"></i>`; }
            statusMessage.textContent = message; statusBox.classList.remove('hidden');
            if (type !== "info") { setTimeout(() => statusBox.classList.add('hidden'), 5000); }
        }

        window.onload = checkAuth;

        // Funkcja ratunkowa w przypadku wykrycia nieaktywnego tokenu w bazie danych
        function forceLogoutDueToExpiredSession() {
            removeAuthToken();
            localStorage.removeItem('user_name');
            authScreen.classList.remove('hidden');
            invoicesList.innerHTML = '';
            showStatus("Twoja sesja wygasła. Zaloguj się ponownie.", "error");
        }
    </script>
</body>
</html>