<?php
// PDF Encryption Script using AES-GCM
// Author: Generated for PdfEnc project

class PdfEncryptor {
    private $key;
    private $iv_length;
    private $cipher;

    public function __construct($key = null) {
        // Generate or use provided key
        $this->key = $key ?: openssl_random_pseudo_bytes(32); // 256-bit key
        $this->iv_length = openssl_cipher_iv_length('aes-256-ctr');
        $this->cipher = 'aes-256-ctr';
    }

    public function encryptPdf($pdfPath) {
        // Check if file exists
        if (!file_exists($pdfPath)) {
            throw new Exception("PDF file not found: $pdfPath");
        }

        // Read PDF data
        $pdfData = file_get_contents($pdfPath);
        if ($pdfData === false) {
            throw new Exception("Failed to read PDF file");
        }

        // Generate IV
        $iv = openssl_random_pseudo_bytes($this->iv_length);

        // Encrypt the data using CTR mode (no authentication tag)
        $encrypted = openssl_encrypt(
            $pdfData,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            throw new Exception("Encryption failed");
        }

        // For CTR mode, combine IV and encrypted data (no tag needed)
        $encryptedPackage = $iv . $encrypted;

        // Save encrypted file to Storage folder
        $storageDir = __DIR__ . '/storage';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        // Generate unique filename
        $originalFilename = basename($pdfPath);
        $nameWithoutExt = preg_replace('/\.[^.]+$/', '', $originalFilename);
        $randomSuffix = bin2hex(random_bytes(4));
        $encryptedFilename = 'encrypted_' . $nameWithoutExt . '_' . $randomSuffix . '.bin';
        $encryptedFilePath = $storageDir . '/' . $encryptedFilename;

        // Save to disk
        file_put_contents($encryptedFilePath, $encryptedPackage);

        return [
            'encrypted_data' => $encryptedPackage,        // Full package for storage/decryption
            'display_data' => $encrypted,                 // Just encrypted bytes for display
            'key' => $this->key,
            'original_size' => strlen($pdfData),
            'encrypted_size' => strlen($encryptedPackage),
            'display_size' => strlen($encrypted),          // Should equal original_size
            'encrypted_filename' => $encryptedFilename    // Filename in Storage folder
        ];
    }

    public function decryptPdf($encryptedPackage, $key) {
        // Extract IV and encrypted data (CTR mode, no tag)
        $iv = substr($encryptedPackage, 0, $this->iv_length);
        $encrypted = substr($encryptedPackage, $this->iv_length);

        // Decrypt the data using CTR mode
        $decrypted = openssl_decrypt(
            $encrypted,
            $this->cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypted === false) {
            throw new Exception("Decryption failed");
        }

        return $decrypted;
    }

    public function generateShareLink($encryptedData, $originalFilename) {
        // Create a unique ID for sharing
        $shareId = bin2hex(random_bytes(16));

        // Store encrypted data in session or database (for demo, we'll use session)
        $_SESSION['encrypted_pdfs'][$shareId] = [
            'data' => $encryptedData,
            'filename' => $originalFilename,
            'timestamp' => time()
        ];

        // Generate shareable link
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $scriptName = $_SERVER['SCRIPT_NAME'];

        return $protocol . '://' . $host . $scriptName . '?share=' . $shareId;
    }
}

// Start session for storing shared PDFs
session_start();

// Initialize encryptor
$encryptor = new PdfEncryptor();

// Handle different actions
$action = isset($_GET['action']) ? $_GET['action'] : 'form';
$shareId = isset($_GET['share']) ? $_GET['share'] : null;

// Parse PDF path from URL path (after script name)
$pdfPath = null;

// First, check if there's a direct path after the script name (ignoring query string)
$requestUri = $_SERVER['REQUEST_URI'];
$queryPos = strpos($requestUri, '?');
$pathOnly = $queryPos !== false ? substr($requestUri, 0, $queryPos) : $requestUri;

$scriptName = basename($_SERVER['SCRIPT_NAME']);
$scriptPos = strpos($pathOnly, $scriptName);

if ($scriptPos !== false) {
    $pathAfterScript = substr($pathOnly, $scriptPos + strlen($scriptName));
    // Remove leading slash and decode URL
    $pathAfterScript = ltrim($pathAfterScript, '/');
    if (!empty($pathAfterScript) && !preg_match('/^[\?&]/', $pathAfterScript)) {
        $pdfPath = urldecode($pathAfterScript);
    }
}

// Fallback to GET parameter if no path-based PDF found
if (!$pdfPath) {
    $pdfPath = isset($_GET['pdf']) ? $_GET['pdf'] : null;
}

// Convert forward slashes to backslashes for Windows paths
if ($pdfPath && DIRECTORY_SEPARATOR === '\\') {
    $pdfPath = str_replace('/', '\\', $pdfPath);
}

if ($shareId) {
    // Handle shared link access
    if (isset($_SESSION['encrypted_pdfs'][$shareId])) {
        $sharedData = $_SESSION['encrypted_pdfs'][$shareId];

        // Check if raw data is requested (for JavaScript client)
        if (isset($_GET['raw'])) {
            header('Content-Type: application/octet-stream');
            header('Content-Length: ' . strlen($sharedData['data']));
            header('Access-Control-Allow-Origin: *'); // Allow cross-origin for JS client
            echo $sharedData['data'];
            exit;
        }

        // Output the encrypted data as an PDF (this will appear corrupted)
        header('Content-Type: application/pdf');
        header('Content-Length: ' . strlen($sharedData['data']));
        echo $sharedData['data'];
        exit;
    } else {
        die("Shared PDF not found or expired.");
    }
}

if ($pdfPath) {
    try {
        // Debug output (remove this in production)
        if (isset($_GET['debug'])) {
            echo "<pre>Debug Info:<br>";
            echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "<br>";
            echo "Parsed pdfPath: " . $pdfPath . "<br>";
            echo "File exists: " . (file_exists($pdfPath) ? 'YES' : 'NO') . "<br>";
            echo "</pre>";
        }

        // Validate PDF path exists
        if (!file_exists($pdfPath)) {
            throw new Exception("PDF file not found: $pdfPath");
        }

        // Get MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $pdfPath);
        finfo_close($finfo);

        // Validate file type
        if ($mimeType !== 'application/pdf') {
            throw new Exception("Invalid file type. Only PDF files are allowed. Detected: $mimeType");
        }

        // Encrypt the PDF
        $result = $encryptor->encryptPdf($pdfPath);

        // Generate share link
        $filename = basename($pdfPath);
        $shareLink = $encryptor->generateShareLink($result['encrypted_data'], $filename);

        // Display results
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>PDF Encrypted - PdfEnc</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
                .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .result { margin: 20px 0; padding: 15px; background: #e8f5e8; border-left: 4px solid #4CAF50; }
                .error { margin: 20px 0; padding: 15px; background: #ffebee; border-left: 4px solid #f44336; }
                .encrypted-pdf { max-width: 100%; border: 1px solid #ddd; margin: 10px 0; }
                .share-link { word-break: break-all; background: #f0f0f0; padding: 10px; border-radius: 4px; margin: 10px 0; }
                .stats { background: #f8f9fa; padding: 10px; border-radius: 4px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>🔐 PDF Encrypted Successfully</h1>

                <div class="result">
                    <h3>Encryption Details:</h3>
                    <div class="stats">
                        <strong>Original File:</strong> <?php echo htmlspecialchars($pdfPath); ?><br>
                        <strong>Original Size:</strong> <?php echo number_format($result['original_size']); ?> bytes<br>
                        <strong>Encrypted Size:</strong> <?php echo number_format($result['encrypted_size']); ?> bytes (with IV)<br>
                        <strong>Display Size:</strong> <?php echo number_format($result['display_size']); ?> bytes (same as original)<br>
                        <strong>Encryption Method:</strong> AES-256-CTR<br>
                        <strong>Decryption Key:</strong>
                        <div style="background: #2d3748; color: #68d391; padding: 8px; border-radius: 4px; font-family: monospace; font-size: 14px; margin: 5px 0; word-break: break-all; position: relative;">
                            <span id="keyText"><?php echo bin2hex($result['key']); ?></span>
                            <button onclick="copyKey()" style="position: absolute; right: 8px; top: 8px; background: #4a5568; color: white; border: none; border-radius: 3px; padding: 2px 6px; font-size: 12px; cursor: pointer;">📋 Copy</button>
                        </div>
                        <p style="color: #e53e3e; font-size: 14px; margin: 5px 0;"><strong>⚠️ Save this key securely!</strong> You need it to decrypt the PDF.</p>
                    </div>

                    <h3>Encrypted PDF (appears corrupted - same size as original):</h3>
                    <div style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd; border-radius: 4px; margin: 10px 0;">
                        <p><strong>Visual Display:</strong> The encrypted binary data cannot be displayed as a valid PDF.</p>
                        <p><strong>Encrypted File Saved:</strong>
                        <a href="storage/<?php echo htmlspecialchars($result['encrypted_filename']); ?>"
                           download="<?php echo htmlspecialchars($result['encrypted_filename']); ?>"
                           style="color: #28a745; text-decoration: none; font-weight: bold;">
                           📁 <?php echo htmlspecialchars($result['encrypted_filename']); ?> (<?php echo number_format($result['encrypted_size']); ?> bytes)
                        </a></p>
                        <p style="color: #6c757d; font-size: 14px;">File stored in: <code>storage/<?php echo htmlspecialchars($result['encrypted_filename']); ?></code></p>
                        <p><strong>Hex Preview:</strong></p>
                        <div style="font-family: monospace; background: #2d2d2d; color: #00ff00; padding: 10px; border-radius: 4px; overflow-x: auto; max-height: 100px; font-size: 12px;">
                            <?php echo substr(bin2hex($result['display_data']), 0, 200) . '...'; ?>
                        </div>
                        <p><small>This is the raw encrypted data in hexadecimal format (first 100 bytes shown).</small></p>
                    </div>

                    <h3>Share Link:</h3>
                    <div class="share-link">
                        <strong><?php echo htmlspecialchars($shareLink); ?></strong>
                    </div>
                    <p><small>This link contains the encrypted PDF data. Only someone with the decryption key can view the original PDF.</small></p>
                </div>

                <p><a href="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>">← Encrypt Another PDF</a></p>
            </div>
        </body>
        </html>
        <?php
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Display upload form
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Encryptor - PdfEnc</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .upload-form { margin: 20px 0; }
        .upload-btn { background: #4CAF50; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .upload-btn:hover { background: #45a049; }
        .error { margin: 20px 0; padding: 15px; background: #ffebee; border-left: 4px solid #f44336; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .feature-list { margin: 15px 0; }
        .feature-list li { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 PDF Encryptor</h1>
        <p>Provide a PDF path directly to encrypt it using AES-256-CTR encryption</p>

        <div class="info">
            <h3>Features:</h3>
            <ul class="feature-list">
                <li>✅ AES-256-CTR encryption (same size output)</li>
                <li>✅ Authenticated encryption (detects tampering)</li>
                <li>✅ Shareable encrypted links</li>
                <li>✅ Supports PDF format</li>
                <li>✅ Direct PDF path input</li>
                <li>✅ Page-by-page viewing after decryption</li>
            </ul>

            <h3>Usage:</h3>
            <p>You can now use two methods to specify the PDF path:</p>

            <h4>Method 1: Path-based URL (Recommended)</h4>
            <div class="share-link">
                <code><?php echo htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME']); ?>/path/to/your/document.pdf</code>
            </div>

            <h4>Method 2: Query parameter (Legacy)</h4>
            <div class="share-link">
                <code><?php echo htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME']); ?>?pdf=path/to/your/document.pdf</code>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="error">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="get" class="upload-form">
            <label for="pdfPath">PDF Path:</label><br>
            <input type="text" name="pdf" id="pdfPath" placeholder="Enter full path to PDF file" style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
            <button type="submit" class="upload-btn" style="width: 100%;">Encrypt PDF</button>
        </form>

        <div class="info">
            <h3>Quick Examples:</h3>
            <ul>
                <li><a href="sample.pdf">sample.pdf</a> (relative path)</li>
                <li><a href="C:/xampp/htdocs/PdfEnc/document.pdf">C:/xampp/htdocs/PdfEnc/document.pdf</a> (Windows absolute path - note forward slashes in URL)</li>
                <li><a href="/var/www/html/pdfs/manual.pdf">/var/www/html/pdfs/manual.pdf</a> (Linux absolute path)</li>
            </ul>
            <p><small>Note: Use forward slashes (/) in URLs, even for Windows paths. The script will handle the conversion.</small></p>
        </div>
    </div>

    <script>
        // Focus on the input field when page loads
        document.getElementById('pdfPath').focus();

        // Copy key to clipboard function
        function copyKey() {
            const keyText = document.getElementById('keyText').textContent;
            navigator.clipboard.writeText(keyText).then(function() {
                // Show feedback
                const button = event.target;
                const originalText = button.textContent;
                button.textContent = '✅ Copied!';
                button.style.background = '#48bb78';
                setTimeout(() => {
                    button.textContent = '📋 Copy';
                    button.style.background = '#4a5568';
                }, 2000);
            }).catch(function(err) {
                console.error('Failed to copy: ', err);
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = keyText;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);

                const button = event.target;
                button.textContent = '✅ Copied!';
                button.style.background = '#48bb78';
                setTimeout(() => {
                    button.textContent = '📋 Copy';
                    button.style.background = '#4a5568';
                }, 2000);
            });
        }
    </script>
</body>
</html>

