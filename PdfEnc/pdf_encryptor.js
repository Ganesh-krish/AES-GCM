class PdfDecryptor {
    constructor() {
        this.key = null;
        this.ivLength = 16;
        this.pdfDocument = null;
        this.currentPage = 1;
        this.totalPages = 0;
        this.scale = 1.5; // Default scale for better readability
    }

    hexToBytes(hex) {
        const bytes = new Uint8Array(hex.length / 2);
        for (let i = 0; i < bytes.length; i++) {
            bytes[i] = parseInt(hex.substr(i * 2, 2), 16);
        }
        return bytes;
    }

    async importKey(keyHex) {
        const keyBytes = this.hexToBytes(keyHex);
        this.key = await crypto.subtle.importKey(
            'raw',
            keyBytes,
            'AES-CTR',
            false,
            ['decrypt']
        );
        return this.key;
    }

    async decryptPdf(encryptedData, keyHex) {
        if (!this.key) {
            await this.importKey(keyHex);
        }

        const iv = encryptedData.slice(0, this.ivLength);
        const encrypted = encryptedData.slice(this.ivLength);

        const decrypted = await crypto.subtle.decrypt(
            {
                name: 'AES-CTR',
                counter: iv,
                length: 128
            },
            this.key,
            encrypted
        );

        return new Uint8Array(decrypted);
    }

    readFileAsArrayBuffer(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(new Uint8Array(reader.result));
            reader.onerror = () => reject(new Error('Failed to read file'));
            reader.readAsArrayBuffer(file);
        });
    }

    async loadPdfFromData(pdfData) {
        try {
            console.log('Loading PDF from decrypted data, size:', pdfData.length);
            const pdfBlob = new Blob([pdfData], { type: 'application/pdf' });
            const pdfUrl = URL.createObjectURL(pdfBlob);

            console.log('PDF blob URL created:', pdfUrl);

            this.pdfDocument = await pdfjsLib.getDocument(pdfUrl).promise;
            this.totalPages = this.pdfDocument.numPages;
            this.currentPage = 1;

            console.log('PDF loaded successfully, pages:', this.totalPages);

            // Clean up the blob URL after loading
            URL.revokeObjectURL(pdfUrl);

            return this.pdfDocument;
        } catch (error) {
            console.error('Error loading PDF:', error);
            throw new Error('Failed to load PDF: ' + error.message);
        }
    }

    async renderPage(pageNumber, container) {
        try {
            if (!this.pdfDocument) {
                throw new Error('PDF document not loaded');
            }

            const page = await this.pdfDocument.getPage(pageNumber);
            const viewport = page.getViewport({ scale: this.scale });

            // Create canvas
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            canvas.height = viewport.height;
            canvas.width = viewport.width;
            canvas.className = 'pdf-canvas';

            // Clear container and add new canvas
            container.innerHTML = '';
            container.appendChild(canvas);

            // Render page
            const renderContext = {
                canvasContext: context,
                viewport: viewport
            };

            await page.render(renderContext).promise;
            console.log('Page', pageNumber, 'rendered successfully');

            return canvas;
        } catch (error) {
            console.error('Error rendering page:', error);
            container.innerHTML = '<div style="color: red; padding: 20px;">Error rendering page: ' + error.message + '</div>';
            throw error;
        }
    }

    updatePageNavigation() {
        const prevBtn = document.getElementById('prevPage');
        const nextBtn = document.getElementById('nextPage');
        const pageInfo = document.getElementById('pageInfo');

        if (prevBtn && nextBtn && pageInfo) {
            prevBtn.disabled = this.currentPage <= 1;
            nextBtn.disabled = this.currentPage >= this.totalPages;
            pageInfo.textContent = `Page ${this.currentPage} of ${this.totalPages}`;
        }
    }

    async goToPage(pageNumber) {
        if (pageNumber < 1 || pageNumber > this.totalPages) {
            return;
        }

        this.currentPage = pageNumber;
        const container = document.getElementById('pdfContainer');
        if (container) {
            await this.renderPage(this.currentPage, container);
            this.updatePageNavigation();
        }
    }

    async nextPage() {
        if (this.currentPage < this.totalPages) {
            await this.goToPage(this.currentPage + 1);
        }
    }

    async prevPage() {
        if (this.currentPage > 1) {
            await this.goToPage(this.currentPage - 1);
        }
    }

    downloadPdf(pdfData, filename = 'decrypted.pdf') {
        const blob = new Blob([pdfData], { type: 'application/pdf' });
        const url = URL.createObjectURL(blob);

        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.click();

        // Clean up
        URL.revokeObjectURL(url);
    }

    detectFilename(filename) {
        // Try to detect original filename from the encrypted filename
        let originalFilename = filename.replace('encrypted_', '').replace(/_\w{8}\.bin$/, '');
        if (!originalFilename.includes('.')) {
            originalFilename += '.pdf'; // default extension
        }
        return originalFilename;
    }
}

const decryptor = new PdfDecryptor();
let originalPdfData = null;
let originalFilename = 'decrypted.pdf';

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('decryptForm');
    const fileInput = document.getElementById('encryptedFile');
    const keyInput = document.getElementById('decryptionKey');
    const decryptBtn = document.getElementById('decryptBtn');
    const statusDiv = document.getElementById('status');
    const resultDiv = document.getElementById('result');
    const downloadBtn = document.getElementById('downloadBtn');

    // Navigation buttons
    const prevBtn = document.getElementById('prevPage');
    const nextBtn = document.getElementById('nextPage');

    console.log('DOM Elements found:', {
        form: !!form,
        fileInput: !!fileInput,
        keyInput: !!keyInput,
        decryptBtn: !!decryptBtn
    });

    if (!form) {
        console.error('Form element not found! Check HTML structure.');
        return;
    }

    function validateForm() {
        const hasFile = fileInput.files.length > 0;
        const keyValue = keyInput.value.trim();
        const hasKey = keyValue.length === 64 && /^[a-fA-F0-9]+$/.test(keyValue);
        decryptBtn.disabled = !(hasFile && hasKey);

        console.log('Validation:', { hasFile, keyLength: keyValue.length, hasKey, buttonDisabled: decryptBtn.disabled });
    }

    fileInput.addEventListener('change', validateForm);
    keyInput.addEventListener('input', validateForm);

    // Navigation event listeners
    if (prevBtn) {
        prevBtn.addEventListener('click', () => decryptor.prevPage());
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', () => decryptor.nextPage());
    }

    // Download button
    if (downloadBtn) {
        downloadBtn.addEventListener('click', () => {
            if (originalPdfData) {
                decryptor.downloadPdf(originalPdfData, originalFilename);
            }
        });
    }

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const file = fileInput.files[0];
        const keyHex = keyInput.value.trim();

        if (!file || !keyHex) {
            showStatus('Please select a file and enter a valid key', 'error');
            return;
        }

        try {
            showStatus('Decrypting PDF...', '');
            decryptBtn.disabled = true;

            // Read encrypted file
            const encryptedData = await decryptor.readFileAsArrayBuffer(file);
            console.log('Encrypted file read, size:', encryptedData.length);

            // Decrypt the data
            const decryptedData = await decryptor.decryptPdf(encryptedData, keyHex);
            console.log('PDF decrypted successfully, size:', decryptedData.length);

            // Store decrypted data for download
            originalPdfData = decryptedData;

            // Detect original filename
            originalFilename = decryptor.detectFilename(file.name);
            console.log('Original filename detected:', originalFilename);

            // Load PDF document
            await decryptor.loadPdfFromData(decryptedData);

            // Render first page
            const container = document.getElementById('pdfContainer');
            await decryptor.renderPage(1, container);

            // Update navigation
            decryptor.updatePageNavigation();

            // Show result
            resultDiv.style.display = 'block';
            downloadBtn.style.display = 'inline-block';
            showStatus('✅ Successfully decrypted and loaded PDF!', 'success');

        } catch (error) {
            console.error('Decryption failed:', error);
            showStatus('❌ Decryption failed: ' + error.message, 'error');
        } finally {
            decryptBtn.disabled = false;
        }
    });

    function showStatus(message, type) {
        statusDiv.textContent = message;
        statusDiv.className = 'status';
        if (type) statusDiv.classList.add(type);
        statusDiv.style.display = message ? 'block' : 'none';
    }
});

