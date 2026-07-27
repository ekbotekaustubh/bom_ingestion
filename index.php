<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOM Ingestion & Analysis System | Industrial Decision Intelligence</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts: Inter & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at 50% 50%, #1e1b4b 0%, #0f172a 100%);
        }
        .font-outfit {
            font-family: 'Outfit', sans-serif;
        }
        .glass-panel {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .glow-button {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .glow-button::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: all 0.5s ease;
        }
        .glow-button:hover::after {
            left: 100%;
        }
    </style>
</head>
<body class="min-h-screen text-slate-100 flex flex-col justify-between antialiased">

    <!-- Header / Nav -->
    <header class="w-full max-w-7xl mx-auto px-6 py-6 flex justify-between items-center">
        <div class="flex items-center space-x-3">
            <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-indigo-500 to-violet-600 flex items-center justify-center shadow-lg shadow-indigo-500/30">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
            </div>
            <div>
                <span class="font-outfit font-bold text-lg tracking-wide bg-gradient-to-r from-white via-slate-200 to-slate-400 bg-clip-text text-transparent">BOM INGESTION</span>
                <p class="text-[10px] text-slate-400 font-semibold tracking-widest uppercase">Decision Intelligence</p>
            </div>
        </div>
        <div>
            <span class="text-xs font-semibold px-3 py-1.5 rounded-full bg-slate-800 border border-slate-700 text-indigo-300">v1.1.0</span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center px-4 py-8">
        <div class="w-full max-w-2xl glass-panel rounded-3xl p-8 md:p-12 shadow-2xl relative overflow-hidden">
            <!-- Decorative gradient blobs -->
            <div class="absolute -top-24 -left-24 w-48 h-48 bg-indigo-500/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-24 -right-24 w-48 h-48 bg-violet-500/10 rounded-full blur-3xl"></div>

            <!-- Tab Switcher -->
            <div class="flex border-b border-slate-800/80 mb-8 relative z-20">
                <button id="tab-ingest" class="flex-1 pb-4 text-sm font-semibold tracking-wider uppercase border-b-2 border-indigo-500 text-indigo-400 focus:outline-none transition-all duration-300">
                    Ingest BOM
                </button>
                <button id="tab-explode" class="flex-1 pb-4 text-sm font-semibold tracking-wider uppercase border-b-2 border-transparent text-slate-400 hover:text-slate-200 focus:outline-none transition-all duration-300">
                    Explode BOM
                </button>
            </div>

            <!-- Ingest BOM View -->
            <div id="upload-container" class="relative z-10 space-y-6">
                <!-- Title Section -->
                <div class="text-center mb-8">
                    <h1 class="font-outfit text-3xl md:text-4xl font-extrabold text-white tracking-tight mb-3">
                        Upload Bill of Materials
                    </h1>
                    <p class="text-slate-400 text-sm md:text-base max-w-md mx-auto">
                        Upload your BOM document to ingest products, parts, and hierarchical relationships into the database.
                    </p>
                </div>

                <!-- Form -->
                <form id="uploadForm" action="api/v1/process.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                    
                    <!-- File Dropzone -->
                    <div id="dropzone" class="border-2 border-dashed border-slate-600 hover:border-indigo-500 active:border-indigo-400 rounded-2xl p-8 text-center cursor-pointer transition-all duration-300 bg-slate-900/40 relative group">
                        <input type="file" name="bom_file" id="bom_file" class="hidden" accept=".csv,.json,.xls,.xlsx" required>
                        
                        <!-- Icon & Helper Text -->
                        <div id="dropzone-prompt" class="space-y-4">
                            <div class="mx-auto w-16 h-16 rounded-2xl bg-slate-800/80 flex items-center justify-center border border-slate-700 group-hover:scale-110 group-hover:border-indigo-500/50 group-hover:bg-indigo-950/20 transition-all duration-300">
                                <svg class="w-8 h-8 text-slate-400 group-hover:text-indigo-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-base font-semibold text-slate-200">
                                    Drag and drop your file here, or <span class="text-indigo-400 group-hover:text-indigo-300 transition-colors underline decoration-2 underline-offset-4">browse</span>
                                </p>
                                <p class="text-xs text-slate-400 mt-2">
                                    Supports CSV, JSON, XLS, and XLSX formats up to 10MB
                                </p>
                            </div>
                        </div>

                        <!-- Selected File Preview -->
                        <div id="file-preview" class="hidden space-y-4">
                            <div class="mx-auto w-16 h-16 rounded-2xl bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center">
                                <svg id="file-icon-default" class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <p id="preview-filename" class="text-sm font-semibold text-slate-200 truncate max-w-xs mx-auto"></p>
                                <p id="preview-filesize" class="text-xs text-indigo-300 mt-1"></p>
                            </div>
                            <button type="button" id="remove-file-btn" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-rose-950/40 hover:text-rose-400 border border-slate-700 hover:border-rose-900/50 transition-all">
                                Change File
                            </button>
                        </div>
                    </div>

                    <!-- Client-side Error Message -->
                    <div id="error-message" class="hidden flex items-start space-x-3 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-sm">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <span id="error-text">Please upload a valid file.</span>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" id="submit-btn" class="w-full glow-button bg-gradient-to-r from-indigo-500 to-violet-600 hover:from-indigo-600 hover:to-violet-700 text-white font-semibold py-4 px-6 rounded-2xl shadow-lg shadow-indigo-500/20 hover:shadow-indigo-500/30 transition-all duration-300 flex items-center justify-center space-x-3 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span>Ingest BOM Document</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
                        </svg>
                    </button>

                    <!-- Loading / Processing indicator (hidden by default) -->
                    <div id="loading-state" class="hidden flex flex-col items-center justify-center space-y-3 pt-2">
                        <div class="flex items-center space-x-2">
                            <svg class="animate-spin h-5 w-5 text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm font-medium text-slate-300">Processing file and mapping schema...</span>
                        </div>
                        <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-indigo-500 h-1.5 rounded-full animate-[pulse_1.5s_infinite]" style="width: 75%"></div>
                        </div>
                    </div>
                </form>

                <!-- Format Badges -->
                <div class="mt-8 border-t border-slate-800/80 pt-6">
                    <p class="text-xs text-slate-400 text-center uppercase tracking-widest font-semibold mb-4">Supported File Types</p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="flex items-center space-x-2 bg-slate-900/60 border border-slate-800/80 rounded-xl px-3 py-2">
                            <div class="text-emerald-400 text-xs font-bold px-1.5 py-0.5 rounded bg-emerald-950/50 border border-emerald-900/40">CSV</div>
                            <span class="text-xs text-slate-300 font-medium">Comma Separated</span>
                        </div>
                        <div class="flex items-center space-x-2 bg-slate-900/60 border border-slate-800/80 rounded-xl px-3 py-2">
                            <div class="text-amber-400 text-xs font-bold px-1.5 py-0.5 rounded bg-amber-950/50 border border-amber-900/40">JSON</div>
                            <span class="text-xs text-slate-300 font-medium">Structured Tree</span>
                        </div>
                        <div class="flex items-center space-x-2 bg-slate-900/60 border border-slate-800/80 rounded-xl px-3 py-2">
                            <div class="text-blue-400 text-xs font-bold px-1.5 py-0.5 rounded bg-blue-950/50 border border-blue-900/40">XLSX</div>
                            <span class="text-xs text-slate-300 font-medium">Excel Spreadsheet</span>
                        </div>
                        <div class="flex items-center space-x-2 bg-slate-900/60 border border-slate-800/80 rounded-xl px-3 py-2">
                            <div class="text-cyan-400 text-xs font-bold px-1.5 py-0.5 rounded bg-cyan-950/50 border border-cyan-900/40">XLS</div>
                            <span class="text-xs text-slate-300 font-medium">Legacy Excel</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ingest Result View -->
            <div id="result-container" class="hidden relative z-10 space-y-6">
                <!-- Checkmark Icon with Pulse -->
                <div class="text-center">
                    <div class="mx-auto w-20 h-20 rounded-full bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center mb-4 relative">
                        <div class="absolute inset-0 rounded-full bg-emerald-500/5 animate-[ping_1.5s_infinite]"></div>
                        <svg class="w-10 h-10 text-emerald-400 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h2 class="font-outfit text-2xl md:text-3xl font-extrabold text-white tracking-tight mb-2">
                        BOM Ingested Successfully
                    </h2>
                    <p id="result-message" class="text-slate-400 text-sm max-w-md mx-auto"></p>
                </div>

                <!-- Metadata Grid -->
                <div class="bg-slate-900/50 border border-slate-800/80 rounded-2xl p-6 grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1">File Name</p>
                        <p id="result-filename" class="text-sm font-semibold text-slate-200 truncate"></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1">File Size</p>
                        <p id="result-filesize" class="text-sm font-semibold text-slate-200"></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Detected Format</p>
                        <span id="result-format" class="inline-block text-[11px] font-bold px-2 py-0.5 rounded bg-indigo-950/50 border border-indigo-900/40 text-indigo-300 mt-0.5"></span>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Importer Class</p>
                        <code id="result-importer" class="text-xs font-mono text-indigo-400"></code>
                    </div>
                </div>

                <!-- Parser Output Data -->
                <div class="bg-slate-950/60 rounded-2xl border border-slate-800/80 p-4">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Parsed Data Output</p>
                    <pre id="parsed-output" class="text-xs text-indigo-300 font-mono overflow-auto max-h-48 whitespace-pre-wrap leading-relaxed"></pre>
                </div>

                <!-- Reset Button -->
                <button type="button" id="reset-btn" class="w-full glow-button bg-slate-800 hover:bg-slate-700 text-white font-semibold py-4 px-6 rounded-2xl border border-slate-700 hover:border-indigo-500/50 transition-all duration-300 flex items-center justify-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89H18"></path>
                    </svg>
                    <span>Upload Another Document</span>
                </button>
            </div>

            <!-- Explode BOM View -->
            <div id="explode-container" class="hidden relative z-10 space-y-6">
                <!-- Title Section -->
                <div class="text-center mb-8">
                    <h1 class="font-outfit text-3xl md:text-4xl font-extrabold text-white tracking-tight mb-3">
                        Explode Bill of Materials
                    </h1>
                    <p class="text-slate-400 text-sm md:text-base max-w-md mx-auto">
                        Select an ingested top-level product to view its fully exploded sub-components and rolled-up quantities.
                    </p>
                </div>

                <!-- Selection Dropdown and Action -->
                <div class="space-y-4">
                    <label for="product-select" class="block text-xs font-semibold text-slate-400 uppercase tracking-widest">Select Product</label>
                    <div class="relative">
                        <select id="product-select" class="w-full bg-slate-900/60 border border-slate-700 rounded-2xl px-4 py-4 text-slate-200 focus:outline-none focus:border-indigo-500 transition-all appearance-none cursor-pointer">
                            <option value="">-- Loading products... --</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>

                    <button id="action-explode-btn" class="w-full glow-button bg-gradient-to-r from-indigo-500 to-violet-600 hover:from-indigo-600 hover:to-violet-700 text-white font-semibold py-4 px-6 rounded-2xl shadow-lg shadow-indigo-500/20 hover:shadow-indigo-500/30 transition-all duration-300 flex items-center justify-center space-x-3">
                        <span>Explode BOM</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </button>
                </div>

                <!-- Explode Error Message -->
                <div id="explode-error" class="hidden flex items-start space-x-3 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-sm">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <span id="explode-error-text">Failed to explode BOM.</span>
                </div>

                <!-- Results Table -->
                <div id="exploded-result" class="hidden space-y-4">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Exploded BOM Rollup</p>
                    <div class="border border-slate-800 rounded-2xl overflow-hidden bg-slate-950/40">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-900/80 border-b border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                        <th class="px-6 py-4">Part Number</th>
                                        <th class="px-6 py-4">Description</th>
                                        <th class="px-6 py-4 text-right">Total Qty</th>
                                        <th class="px-6 py-4">UOM</th>
                                    </tr>
                                </thead>
                                <tbody id="exploded-table-body" class="divide-y divide-slate-800/60 text-sm text-slate-300">
                                    <!-- Populated dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="w-full text-center py-6 text-xs text-slate-500 border-t border-slate-900/40 bg-slate-950/20">
        <p>&copy; 2026 Industrial Decision Intelligence. All rights reserved.</p>
    </footer>

    <!-- Frontend Interactive Script -->
    <script>
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('bom_file');
        const promptView = document.getElementById('dropzone-prompt');
        const previewView = document.getElementById('file-preview');
        const filenameSpan = document.getElementById('preview-filename');
        const filesizeSpan = document.getElementById('preview-filesize');
        const removeBtn = document.getElementById('remove-file-btn');
        const errorMsg = document.getElementById('error-message');
        const errorText = document.getElementById('error-text');
        const uploadForm = document.getElementById('uploadForm');
        const submitBtn = document.getElementById('submit-btn');
        const loadingState = document.getElementById('loading-state');

        const ALLOWED_EXTENSIONS = ['csv', 'json', 'xls', 'xlsx'];
        const MAX_SIZE_MB = 10;

        // Trigger file input dialog
        dropzone.addEventListener('click', (e) => {
            if (e.target !== removeBtn) {
                fileInput.click();
            }
        });

        // Prevent browser defaults for drag events
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        // Handle hover states for dragover
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, () => {
                dropzone.classList.add('border-indigo-400', 'bg-indigo-950/10');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, () => {
                dropzone.classList.remove('border-indigo-400', 'bg-indigo-950/10');
            }, false);
        });

        // Handle drop event
        dropzone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length > 0) {
                fileInput.files = files;
                handleFileSelected(files[0]);
            }
        });

        // Handle file select via dialog
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                handleFileSelected(fileInput.files[0]);
            }
        });

        // Process selected file
        function handleFileSelected(file) {
            hideError();
            
            // Check file type
            const extension = file.name.split('.').pop().toLowerCase();
            if (!ALLOWED_EXTENSIONS.includes(extension)) {
                showError(`Invalid file format: .${extension}. Only CSV, JSON, XLS, and XLSX files are supported.`);
                resetFile();
                return;
            }

            // Check size
            const sizeInMB = file.size / (1024 * 1024);
            if (sizeInMB > MAX_SIZE_MB) {
                showError(`File size exceeds limit (${sizeInMB.toFixed(2)} MB). Max limit is ${MAX_SIZE_MB} MB.`);
                resetFile();
                return;
            }

            // Show preview
            promptView.classList.add('hidden');
            previewView.classList.remove('hidden');
            filenameSpan.textContent = file.name;
            filesizeSpan.textContent = formatBytes(file.size);
        }

        // Remove selected file
        removeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            resetFile();
        });

        function resetFile() {
            fileInput.value = '';
            previewView.classList.add('hidden');
            promptView.classList.remove('hidden');
            hideError();
        }

        // Show error message
        function showError(msg) {
            errorText.textContent = msg;
            errorMsg.classList.remove('hidden');
        }

        // Hide error message
        function hideError() {
            errorMsg.classList.add('hidden');
        }

        // Helper to format file size
        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }

        const uploadContainer = document.getElementById('upload-container');
        const resultContainer = document.getElementById('result-container');
        const resultMessage = document.getElementById('result-message');
        const resultFilename = document.getElementById('result-filename');
        const resultFilesize = document.getElementById('result-filesize');
        const resultFormat = document.getElementById('result-format');
        const resultImporter = document.getElementById('result-importer');
        const parsedOutput = document.getElementById('parsed-output');
        const resetBtn = document.getElementById('reset-btn');

        // Form submission behavior (AJAX)
        uploadForm.addEventListener('submit', (e) => {
            e.preventDefault();

            if (fileInput.files.length === 0) {
                showError('Please select a file to upload.');
                return;
            }
            
            // Show loading animation
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50');
            submitBtn.querySelector('span').textContent = 'Uploading & Parsing...';
            loadingState.classList.remove('hidden');
            hideError();

            const formData = new FormData(uploadForm);

            fetch('api/v1/process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw err; });
                }
                return response.json();
            })
            .then(data => {
                // Reset submit button state
                resetSubmitButton();

                if (data.status === 'success') {
                    // Populate result screen
                    resultMessage.textContent = data.message;
                    resultFilename.textContent = data.file_name;
                    resultFilesize.textContent = formatBytes(data.file_size);
                    resultFormat.textContent = data.detected_format;
                    resultImporter.textContent = data.importer_class;
                    parsedOutput.textContent = JSON.stringify(data.parsed_data, null, 2);

                    // Transition view
                    uploadContainer.classList.add('hidden');
                    resultContainer.classList.remove('hidden');
                } else {
                    showError(data.message || 'An error occurred during ingestion.');
                }
            })
            .catch(error => {
                resetSubmitButton();
                const errorTextMsg = error.message || 'Network error or server failed to respond.';
                showError(errorTextMsg);
            });
        });

        // Reset submit button visual state
        function resetSubmitButton() {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50');
            submitBtn.querySelector('span').textContent = 'Ingest BOM Document';
            loadingState.classList.add('hidden');
        }

        // Reset to upload view
        resetBtn.addEventListener('click', () => {
            resultContainer.classList.add('hidden');
            uploadContainer.classList.remove('hidden');
            resetFile();
        });


        // ==========================================
        // EXPLODE BOM VIEW SCRIPTS
        // ==========================================

        const tabIngest = document.getElementById('tab-ingest');
        const tabExplode = document.getElementById('tab-explode');
        const explodeContainer = document.getElementById('explode-container');
        const productSelect = document.getElementById('product-select');
        const actionExplodeBtn = document.getElementById('action-explode-btn');
        const explodeError = document.getElementById('explode-error');
        const explodeErrorText = document.getElementById('explode-error-text');
        const explodedResult = document.getElementById('exploded-result');
        const explodedTableBody = document.getElementById('exploded-table-body');

        tabIngest.addEventListener('click', () => {
            setActiveTab('ingest');
        });

        tabExplode.addEventListener('click', () => {
            setActiveTab('explode');
            loadProducts();
        });

        function setActiveTab(tab) {
            if (tab === 'ingest') {
                tabIngest.className = "flex-1 pb-4 text-sm font-semibold tracking-wider uppercase border-b-2 border-indigo-500 text-indigo-400 focus:outline-none transition-all duration-300";
                tabExplode.className = "flex-1 pb-4 text-sm font-semibold tracking-wider uppercase border-b-2 border-transparent text-slate-400 hover:text-slate-200 focus:outline-none transition-all duration-300";
                
                // Show either upload-container or result-container (depending on state)
                if (resultContainer.classList.contains('hidden')) {
                    uploadContainer.classList.remove('hidden');
                } else {
                    resultContainer.classList.remove('hidden');
                }
                explodeContainer.classList.add('hidden');
            } else {
                tabExplode.className = "flex-1 pb-4 text-sm font-semibold tracking-wider uppercase border-b-2 border-indigo-500 text-indigo-400 focus:outline-none transition-all duration-300";
                tabIngest.className = "flex-1 pb-4 text-sm font-semibold tracking-wider uppercase border-b-2 border-transparent text-slate-400 hover:text-slate-200 focus:outline-none transition-all duration-300";
                
                uploadContainer.classList.add('hidden');
                resultContainer.classList.add('hidden');
                explodeContainer.classList.remove('hidden');
            }
        }

        // Fetch products list
        function loadProducts() {
            productSelect.innerHTML = '<option value="">-- Loading products... --</option>';
            fetch('api/v1/exploded.php')
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        if (data.data.length === 0) {
                            productSelect.innerHTML = '<option value="">No top-level products found. Upload one first.</option>';
                        } else {
                            productSelect.innerHTML = '<option value="">-- Select a Product --</option>';
                            data.data.forEach(prod => {
                                const opt = document.createElement('option');
                                opt.value = prod.id;
                                opt.textContent = `${prod.name} (${prod.part_no})`;
                                productSelect.appendChild(opt);
                            });
                        }
                    } else {
                        productSelect.innerHTML = '<option value="">Failed to load products.</option>';
                    }
                })
                .catch(err => {
                    productSelect.innerHTML = '<option value="">Error connecting to API.</option>';
                });
        }

        // Explode button click handler
        actionExplodeBtn.addEventListener('click', () => {
            const partId = productSelect.value;
            if (!partId) {
                showExplodeError('Please select a product first.');
                return;
            }

            hideExplodeError();
            explodedResult.classList.add('hidden');
            actionExplodeBtn.disabled = true;
            actionExplodeBtn.querySelector('span').textContent = 'Exploding...';

            fetch(`api/v1/exploded.php?part_id=${partId}`)
                .then(res => {
                    if (!res.ok) {
                        return res.json().then(err => { throw err; });
                    }
                    return res.json();
                })
                .then(data => {
                    actionExplodeBtn.disabled = false;
                    actionExplodeBtn.querySelector('span').textContent = 'Explode BOM';

                    if (data.status === 'success') {
                        renderExplodedTable(data.data);
                    } else {
                        showExplodeError(data.message || 'Failed to explode BOM.');
                    }
                })
                .catch(err => {
                    actionExplodeBtn.disabled = false;
                    actionExplodeBtn.querySelector('span').textContent = 'Explode BOM';
                    showExplodeError(err.message || 'Error occurred while contacting server.');
                });
        });

        function renderExplodedTable(parts) {
            explodedTableBody.innerHTML = '';
            if (parts.length === 0) {
                explodedTableBody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-slate-500 font-medium">No components found beneath this product.</td></tr>';
            } else {
                parts.forEach(part => {
                    const tr = document.createElement('tr');
                    tr.className = "hover:bg-slate-900/40 border-b border-slate-800/40 last:border-0 transition-colors";
                    
                    // Format quantity
                    const qtyFloat = parseFloat(part.quantity);
                    const qtyStr = qtyFloat % 1 === 0 ? qtyFloat.toFixed(0) : qtyFloat.toFixed(4);

                    tr.innerHTML = `
                        <td class="px-6 py-4 font-semibold text-indigo-300 font-mono">${escapeHtml(part.part_no)}</td>
                        <td class="px-6 py-4 font-medium text-slate-200">${escapeHtml(part.name)}</td>
                        <td class="px-6 py-4 text-right font-bold text-emerald-400 font-mono">${qtyStr}</td>
                        <td class="px-6 py-4 text-slate-400">${escapeHtml(part.unit)}</td>
                    `;
                    explodedTableBody.appendChild(tr);
                });
            }
            explodedResult.classList.remove('hidden');
        }

        function showExplodeError(msg) {
            explodeErrorText.textContent = msg;
            explodeError.classList.remove('hidden');
        }

        function hideExplodeError() {
            explodeError.classList.add('hidden');
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/&/g, "&amp;")
                      .replace(/</g, "&lt;")
                      .replace(/>/g, "&gt;")
                      .replace(/"/g, "&quot;")
                      .replace(/'/g, "&#039;");
        }
    </script>
</body>
</html>
