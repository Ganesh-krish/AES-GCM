# Image Encryption System (ImgEnc)

A complete client-server image encryption/decryption system using AES-256-CTR.

## Features

- 🔐 **AES-256-CTR Encryption** - Same size input/output
- 🌐 **Path-based URLs** - Clean URLs like `/path/to/image.jpg`
- 📁 **File Upload Support** - Traditional form uploads
- 🔗 **Shareable Links** - Encrypted data sharing
- 🖥️ **Client-side Decryption** - JavaScript Web Crypto API
- 📱 **Responsive Design** - Works on all devices

## Files

- `image_encryptor.php` - Main PHP encryption server
- `decrypt.html` - JavaScript decryption client
- `image_decryptor.js` - Client-side decryption logic
- `test.php` - Test utilities and examples

## Usage

### Encryption (PHP Server)

#### Method 1: Path-based URL
```
http://localhost/ImgEnc/image_encryptor.php/D:/path/to/image.jpg
```

#### Method 2: Query Parameter
```
http://localhost/ImgEnc/image_encryptor.php?image=D:/path/to/image.jpg
```

#### Method 3: File Upload
Visit `image_encryptor.php` in browser and use the upload form.

### Decryption (JavaScript Client)

#### Method 1: Direct Path URL (Recommended)
```
http://localhost/ImgEnc/decrypt.html/D:/encrypted/image.jpg?key=a1b2c3d4e5f6789012345678901234567890abcdef1234567890abcdef12345678
```

#### Method 2: Manual Upload
1. Open `decrypt.html` in browser
2. Upload encrypted image file
3. Enter the 64-character hex decryption key
4. Click "Decrypt Image"

### Shared Links

Encrypted images can be shared via generated links:
```
http://localhost/ImgEnc/image_encryptor.php?share=abc123...
```

## Technical Details

### Encryption Process
1. **Input**: Image file (any format)
2. **IV Generation**: 16-byte random IV for CTR mode
3. **Encryption**: AES-256-CTR (no size overhead)
4. **Output**: IV + Encrypted data (16 bytes + original size)

### Decryption Process
1. **Input**: Encrypted file + 256-bit key (64 hex chars)
2. **Extract**: IV (first 16 bytes) + encrypted data
3. **Decrypt**: AES-CTR with extracted IV and key
4. **Output**: Original image

## Security Notes

- Keys are 256-bit (32 bytes) AES keys
- CTR mode provides semantic security
- No padding overhead (unlike CBC)
- Client-side decryption keeps keys local
- Server only stores encrypted data in sessions

## Browser Support

- **Encryption**: Server-side (PHP OpenSSL)
- **Decryption**: Modern browsers with Web Crypto API
- **AES-CTR**: Supported in Chrome 12+, Firefox 21+, Safari 10.4+

## API Reference

### PHP Server Endpoints

- `GET /image_encryptor.php/{image_path}` - Encrypt image at path
- `GET /image_encryptor.php?share={id}` - Access shared encrypted image
- `GET /image_encryptor.php?share={id}&raw=1` - Raw encrypted data (for JS client)

### JavaScript Classes

```javascript
const decryptor = new ImageDecryptor();

// Decrypt image data
const decrypted = await decryptor.decryptImage(encryptedData, keyHex);

// Create displayable image
const imageUrl = decryptor.createImageFromData(decryptedData, 'image/png');
```

## Development

1. Place all files in web server document root
2. Ensure PHP OpenSSL extension is enabled
3. Access via HTTP (localhost for development)
4. Use `test.php` for testing and examples

## License

This project demonstrates AES-CTR image encryption for educational purposes.
