class ImageDecryptor {
    constructor() {
        this.key = null;
        this.ivLength = 16;
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

    async decryptImage(encryptedData, keyHex) {
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

    createImageFromData(imageData, mimeType = 'image/png') {
        console.log('Creating blob with type:', mimeType, 'size:', imageData.length);
        const blob = new Blob([imageData], { type: mimeType });
        const url = URL.createObjectURL(blob);
        console.log('Blob URL created:', url);
        return url;
    }

    detectMimeType(filename) {
        const ext = filename.toLowerCase().split('.').pop();
        const mimeTypes = {
            'jpg': 'image/jpeg',
            'jpeg': 'image/jpeg',
            'png': 'image/png',
            'gif': 'image/gif',
            'webp': 'image/webp',
            'bmp': 'image/bmp'
        };
        return mimeTypes[ext] || 'image/png';
    }
}

const decryptor = new ImageDecryptor();
let originalImageUrl = null;

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('decryptForm');
    const fileInput = document.getElementById('encryptedFile');
    const keyInput = document.getElementById('decryptionKey');
    const decryptBtn = document.getElementById('decryptBtn');
    const statusDiv = document.getElementById('status');
    const resultDiv = document.getElementById('result');
    const originalImage = document.getElementById('originalImage');

    // Debug: Check if elements are found
    console.log('DOM Elements found:', {
        form: !!form,
        fileInput: !!fileInput,
        keyInput: !!keyInput,
        decryptBtn: !!decryptBtn,
        statusDiv: !!statusDiv
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

        // Debug logging (remove in production)
        console.log('Validation:', { hasFile, keyLength: keyValue.length, hasKey, buttonDisabled: decryptBtn.disabled });
    }

    fileInput.addEventListener('change', validateForm);
    keyInput.addEventListener('input', validateForm);

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const file = fileInput.files[0];
        const keyHex = keyInput.value.trim();

        if (!file || !keyHex) {
            showStatus('Please select a file and enter a valid key', 'error');
            return;
        }

        try {
            showStatus('Decrypting...', '');
            decryptBtn.disabled = true;

            const encryptedData = await decryptor.readFileAsArrayBuffer(file);
            const decryptedData = await decryptor.decryptImage(encryptedData, keyHex);

            // Try to detect original filename from the encrypted filename
            let originalFilename = file.name.replace('encrypted_', '').replace(/_\w{8}\.bin$/, '');
            if (!originalFilename.includes('.')) {
                originalFilename += '.png'; // default extension
            }

            console.log('Original filename detected:', originalFilename, 'from file:', file.name);

            const mimeType = decryptor.detectMimeType(originalFilename);
            console.log('Creating image with mimeType:', mimeType, 'data length:', decryptedData.length);

            const imageUrl = decryptor.createImageFromData(decryptedData, mimeType);
            console.log('Image URL created:', imageUrl ? 'success' : 'failed');

            originalImage.src = imageUrl;
            originalImage.style.display = 'block';
            originalImageUrl = imageUrl;

            console.log('Image element updated, src set to:', imageUrl);

            // Add error handling for image loading
            originalImage.onerror = function() {
                console.error('Failed to load decrypted image with blob URL, trying data URL...');

                // Fallback: try data URL instead of blob URL
                try {
                    const base64Data = btoa(String.fromCharCode(...decryptedData));
                    const dataUrl = `data:${mimeType};base64,${base64Data}`;
                    originalImage.src = dataUrl;
                    console.log('Switched to data URL:', dataUrl.substring(0, 50) + '...');
                } catch (e) {
                    console.error('Data URL fallback also failed:', e);
                    showStatus('❌ Image display failed, but decryption was successful. Try downloading instead.', 'error');
                }
            };

            originalImage.onload = function() {
                console.log('Image loaded successfully, dimensions:', originalImage.naturalWidth, 'x', originalImage.naturalHeight);
            };

            resultDiv.style.display = 'block';
            showStatus('✅ Successfully decrypted!', 'success');

        } catch (error) {
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

function downloadImage() {
    if (originalImageUrl) {
        const link = document.createElement('a');
        link.href = originalImageUrl;
        link.download = 'decrypted_image.png';
        link.click();
    }
}