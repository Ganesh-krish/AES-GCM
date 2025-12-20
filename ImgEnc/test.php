<?php
// Simple test to demonstrate the image encryptor
// Create a test image and encrypt it

// Create a simple test image (100x100 red square)
$image = imagecreatetruecolor(100, 100);
$red = imagecolorallocate($image, 255, 0, 0);
imagefill($image, 0, 0, $red);

// Add some text to make it more visible
$textColor = imagecolorallocate($image, 255, 255, 255);
imagestring($image, 5, 25, 40, 'TEST IMAGE', $textColor);

// Save the test image
imagepng($image, 'test_image.png');
imagedestroy($image);

// Also create a copy with a Windows-style path name for testing
copy('test_image.png', 'test_windows_image.jpg');

echo "<h2>Test Image Created Successfully!</h2>";
echo "<p>Test image saved as: <strong>test_image.png</strong></p>";

// Get the current script URL for examples
$baseUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/';

echo "<h3>Test the New Path-Based URLs:</h3>";
echo "<ul>";
echo "<li><a href='image_encryptor.php/test_image.png' target='_blank'>Path-based: image_encryptor.php/test_image.png</a></li>";
echo "<li><a href='image_encryptor.php?image=test_image.png' target='_blank'>Query param: image_encryptor.php?image=test_image.png</a></li>";
echo "</ul>";

echo "<h3>For Your Windows Path Example:</h3>";
echo "<p>If you have an image at <code>D:\\ada.jpg</code>, use this URL format:</p>";
echo "<p><strong>URL to type:</strong> <code>http://localhost/ImgEnc/image_encryptor.php/D:/ada.jpg</code></p>";
echo "<p><a href='image_encryptor.php/D:/ada.jpg' target='_blank'>Click to test with D:/ada.jpg</a> (if file exists)</p>";
echo "<p><small>Note: Use forward slashes (/) in URLs even for Windows paths!</small></p>";

echo "<h3>Debug Mode:</h3>";
echo "<p><a href='image_encryptor.php/test_image.png?debug=1' target='_blank'>Test with debug output</a></p>";

echo "<h3>Features:</h3>";
echo "<ul>";
echo "<li>✅ CTR mode encryption (same size output)</li>";
echo "<li>✅ Visual hex preview of encrypted data</li>";
echo "<li>✅ Download encrypted files</li>";
echo "<li>✅ Shareable encrypted links</li>";
echo "</ul>";

echo "<h3>Frontend Integration:</h3>";
echo "<p><a href='decrypt.html' target='_blank'>🔓 Open Decryption Interface</a> - JavaScript client for decrypting images</p>";
echo "<p>The JavaScript decryptor works completely client-side using Web Crypto API.</p>";

echo "<h3>Direct Path Decryption Examples:</h3>";
echo "<p>Just like encryption, you can now decrypt directly from file paths:</p>";
echo "<ul>";
echo "<li><a href='decrypt.html/test_image.png?key=example_key_64_chars_long' target='_blank'>decrypt.html/test_image.png?key=[64-char-hex-key]</a></li>";
echo "<li><a href='decrypt.html/C:/xampp/htdocs/ImgEnc/encrypted_file.jpg?key=key_here' target='_blank'>Full Windows path with key</a></li>";
echo "</ul>";
echo "<p><strong>Note:</strong> Replace 'example_key_64_chars_long' with your actual 64-character hex decryption key.</p>";
?>
