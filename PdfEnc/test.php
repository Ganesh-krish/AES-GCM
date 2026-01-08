<?php
// PDF Encryption Test Suite
// Author: Generated for PdfEnc project

require_once 'pdf_encryptor.php';

class PdfEncryptionTest {
    private $encryptor;
    private $testResults = [];

    public function __construct() {
        $this->encryptor = new PdfEncryptor();
    }

    private function log($message, $type = 'info') {
        $timestamp = date('Y-m-d H:i:s');
        $this->testResults[] = [
            'timestamp' => $timestamp,
            'type' => $type,
            'message' => $message
        ];
        echo "[$timestamp] [$type] $message\n";
    }

    public function runAllTests() {
        $this->log("Starting PDF Encryption Test Suite", 'start');

        $this->testPdfEncryption();
        $this->testPdfDecryption();
        $this->testShareLinkGeneration();
        $this->testErrorHandling();

        $this->log("Test Suite Completed", 'end');
        return $this->testResults;
    }

    private function testPdfEncryption() {
        $this->log("Testing PDF Encryption", 'test');

        // Test with a sample PDF path (you should replace this with an actual PDF path)
        $testPdfPath = 'sample.pdf'; // Replace with actual path

        if (!file_exists($testPdfPath)) {
            $this->log("Sample PDF not found at: $testPdfPath - skipping encryption test", 'warning');
            return;
        }

        try {
            $result = $this->encryptor->encryptPdf($testPdfPath);

            $this->log("Encryption successful", 'success');
            $this->log("Original size: " . number_format($result['original_size']) . " bytes", 'info');
            $this->log("Encrypted size: " . number_format($result['encrypted_size']) . " bytes", 'info');
            $this->log("Key length: " . strlen($result['key']) . " bytes", 'info');
            $this->log("Encrypted file: " . $result['encrypted_filename'], 'info');

            // Verify sizes
            if ($result['original_size'] !== $result['display_size']) {
                $this->log("ERROR: CTR mode should preserve original size!", 'error');
            } else {
                $this->log("✓ CTR mode correctly preserves original size", 'success');
            }

            // Verify encrypted file exists
            $encryptedFilePath = __DIR__ . '/storage/' . $result['encrypted_filename'];
            if (file_exists($encryptedFilePath)) {
                $this->log("✓ Encrypted file saved successfully", 'success');
            } else {
                $this->log("ERROR: Encrypted file not found!", 'error');
            }

            return $result;

        } catch (Exception $e) {
            $this->log("Encryption failed: " . $e->getMessage(), 'error');
            return null;
        }
    }

    private function testPdfDecryption() {
        $this->log("Testing PDF Decryption", 'test');

        // First encrypt a PDF to test decryption
        $testPdfPath = 'sample.pdf';

        if (!file_exists($testPdfPath)) {
            $this->log("Sample PDF not found - skipping decryption test", 'warning');
            return;
        }

        try {
            // Encrypt
            $encryptResult = $this->encryptor->encryptPdf($testPdfPath);

            // Read encrypted file
            $encryptedFilePath = __DIR__ . '/storage/' . $encryptResult['encrypted_filename'];
            $encryptedData = file_get_contents($encryptedFilePath);

            // Decrypt
            $decryptedData = $this->encryptor->decryptPdf($encryptedData, $encryptResult['key']);

            // Verify decryption
            $originalData = file_get_contents($testPdfPath);

            if ($decryptedData === $originalData) {
                $this->log("✓ Decryption successful - data matches original", 'success');
            } else {
                $this->log("ERROR: Decrypted data does not match original!", 'error');
                $this->log("Original size: " . strlen($originalData), 'info');
                $this->log("Decrypted size: " . strlen($decryptedData), 'info');
            }

        } catch (Exception $e) {
            $this->log("Decryption test failed: " . $e->getMessage(), 'error');
        }
    }

    private function testShareLinkGeneration() {
        $this->log("Testing Share Link Generation", 'test');

        $testPdfPath = 'sample.pdf';

        if (!file_exists($testPdfPath)) {
            $this->log("Sample PDF not found - skipping share link test", 'warning');
            return;
        }

        try {
            $encryptResult = $this->encryptor->encryptPdf($testPdfPath);
            $shareLink = $this->encryptor->generateShareLink($encryptResult['encrypted_data'], basename($testPdfPath));

            if (strpos($shareLink, 'share=') !== false) {
                $this->log("✓ Share link generated successfully", 'success');
                $this->log("Share link: " . $shareLink, 'info');
            } else {
                $this->log("ERROR: Invalid share link format", 'error');
            }

        } catch (Exception $e) {
            $this->log("Share link test failed: " . $e->getMessage(), 'error');
        }
    }

    private function testErrorHandling() {
        $this->log("Testing Error Handling", 'test');

        // Test with non-existent file
        try {
            $this->encryptor->encryptPdf('/non/existent/file.pdf');
            $this->log("ERROR: Should have failed with non-existent file", 'error');
        } catch (Exception $e) {
            $this->log("✓ Correctly handled non-existent file: " . $e->getMessage(), 'success');
        }

        // Test with invalid file type (if you have a non-PDF file to test)
        $invalidFile = 'pdf_encryptor.php'; // PHP file should fail
        try {
            $this->encryptor->encryptPdf($invalidFile);
            $this->log("ERROR: Should have failed with invalid file type", 'error');
        } catch (Exception $e) {
            $this->log("✓ Correctly rejected invalid file type: " . $e->getMessage(), 'success');
        }
    }

    public function getTestSummary() {
        $total = count($this->testResults);
        $success = count(array_filter($this->testResults, fn($r) => $r['type'] === 'success'));
        $errors = count(array_filter($this->testResults, fn($r) => $r['type'] === 'error'));

        return [
            'total_tests' => $total,
            'successful' => $success,
            'errors' => $errors,
            'success_rate' => $total > 0 ? round(($success / ($success + $errors)) * 100, 1) : 0
        ];
    }
}

// HTML Output for Browser
if (isset($_GET['run'])) {
    // Run tests and display results
    $testSuite = new PdfEncryptionTest();
    $results = $testSuite->runAllTests();
    $summary = $testSuite->getTestSummary();

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>PDF Encryption Test Results</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
            .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .test-result { margin: 10px 0; padding: 10px; border-left: 4px solid #ccc; }
            .test-result.success { border-left-color: #4CAF50; background: #e8f5e8; }
            .test-result.error { border-left-color: #f44336; background: #ffebee; }
            .test-result.warning { border-left-color: #ff9800; background: #fff3e0; }
            .test-result.info { border-left-color: #2196F3; background: #e3f2fd; }
            .summary { background: #e8f5e8; padding: 15px; border-radius: 4px; margin: 20px 0; }
            .timestamp { color: #666; font-size: 0.9em; }
            .run-btn { background: #4CAF50; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin: 10px 0; }
            .run-btn:hover { background: #45a049; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🧪 PDF Encryption Test Results</h1>

            <div class="summary">
                <h3>Test Summary</h3>
                <p><strong>Total Tests:</strong> <?php echo $summary['total_tests']; ?></p>
                <p><strong>Successful:</strong> <?php echo $summary['successful']; ?></p>
                <p><strong>Errors:</strong> <?php echo $summary['errors']; ?></p>
                <p><strong>Success Rate:</strong> <?php echo $summary['success_rate']; ?>%</p>
            </div>

            <h3>Detailed Results</h3>
            <?php foreach ($results as $result): ?>
                <div class="test-result <?php echo $result['type']; ?>">
                    <div class="timestamp"><?php echo $result['timestamp']; ?></div>
                    <div><?php echo htmlspecialchars($result['message']); ?></div>
                </div>
            <?php endforeach; ?>

            <p><a href="test.php">← Back to Test Menu</a></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Default test menu
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Encryption Test Suite</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .run-btn { background: #4CAF50; color: white; padding: 15px 30px; border: none; border-radius: 4px; cursor: pointer; font-size: 18px; margin: 20px 0; display: block; width: 100%; }
        .run-btn:hover { background: #45a049; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .warning { background: #fff3e0; padding: 15px; border-radius: 4px; margin: 20px 0; border-left: 4px solid #ff9800; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 PDF Encryption Test Suite</h1>

        <div class="info">
            <h3>Available Tests</h3>
            <ul>
                <li>✓ PDF Encryption/Decryption</li>
                <li>✓ Share Link Generation</li>
                <li>✓ Error Handling</li>
                <li>✓ File Validation</li>
            </ul>
        </div>

        <div class="warning">
            <h3>Setup Required</h3>
            <p>Place a sample PDF file named <code>sample.pdf</code> in the PdfEnc directory to run full tests.</p>
            <p>The test suite will skip PDF-specific tests if the sample file is not found.</p>
        </div>

        <form method="get">
            <input type="hidden" name="run" value="1">
            <button type="submit" class="run-btn">🚀 Run Test Suite</button>
        </form>

        <div class="info">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="pdf_encryptor.php">PDF Encryptor</a></li>
                <li><a href="decrypt.html">PDF Decryptor</a></li>
                <li><a href="README.md">Documentation</a></li>
            </ul>
        </div>
    </div>
</body>
</html>

