# PDF Encryption System (PdfEnc)

A complete client-server PDF encryption/decryption system using AES-256-CTR with page-by-page viewing capabilities.

## Features

- 🔐 **AES-256-CTR Encryption** - Same size input/output, no padding overhead
- 🌐 **Path-based URLs** - Clean URLs like `/path/to/document.pdf`
- 📁 **File Upload Support** - Traditional form uploads
- 🔗 **Shareable Links** - Encrypted data sharing
- 📖 **Page-by-Page Viewing** - Navigate through PDF pages after decryption
- 🖥️ **Client-side Decryption** - JavaScript Web Crypto API
- 📱 **Responsive Design** - Works on all devices
- 🔍 **Zoom Support** - Scale PDF pages for better readability

## Files

- `pdf_encryptor.php` - Main PHP encryption server
- `decrypt.html` - JavaScript decryption client with PDF viewer
- `pdf_encryptor.js` - Client-side decryption and PDF.js integration
- `test.php` - Test utilities and examples
- `storage/` - Directory for encrypted PDF files

## Usage

### Encryption (PHP Server)

#### Method 1: Path-based URL
```
http://localhost/PdfEnc/pdf_encryptor.php/D:/path/to/document.pdf
```

#### Method 2: Query Parameter
```
http://localhost/PdfEnc/pdf_encryptor.php?pdf=D:/path/to/document.pdf
```

#### Method 3: File Upload
Visit `pdf_encryptor.php` in browser and use the upload form.

### Decryption (JavaScript Client)

#### Method 1: Direct Upload (Recommended)
1. Open `decrypt.html` in browser
2. Upload encrypted PDF file (.bin)
3. Enter the 64-character hex decryption key
4. Click "Decrypt PDF"
5. Navigate through pages using Previous/Next buttons

#### Method 2: Shared Link Access
```
http://localhost/PdfEnc/pdf_encryptor.php?share=abc123...
```

### Shared Links

Encrypted PDFs can be shared via generated links:
```
http://localhost/PdfEnc/pdf_encryptor.php?share=abc123...
```

## Technical Details

### Encryption Process
1. **Input**: PDF file (any size)
2. **IV Generation**: 16-byte random IV for CTR mode
3. **Encryption**: AES-256-CTR (no size overhead)
4. **Output**: IV + Encrypted data (16 bytes + original size)

### Decryption Process
1. **Input**: Encrypted file + 256-bit key (64 hex chars)
2. **Extract**: IV (first 16 bytes) + encrypted data
3. **Decrypt**: AES-CTR with extracted IV and key
4. **Display**: PDF.js renders decrypted PDF page-by-page

### PDF Viewing Features
- **Page Navigation**: Previous/Next buttons with page counter
- **Zoom Control**: Adjustable scale for readability
- **Responsive**: Adapts to different screen sizes
- **Smooth Scrolling**: Easy navigation through long documents

## Security Notes

- Keys are 256-bit (32 bytes) AES keys in CTR mode
- CTR mode provides semantic security without padding
- Client-side decryption keeps keys local
- Server only stores encrypted data in sessions
- No PDF content is sent to server during decryption

## Browser Support

- **Encryption**: Server-side (PHP OpenSSL)
- **Decryption**: Modern browsers with Web Crypto API
- **PDF Viewing**: PDF.js supported browsers
- **AES-CTR**: Chrome 12+, Firefox 21+, Safari 10.4+

## API Reference

### PHP Server Endpoints

- `GET /pdf_encryptor.php/{pdf_path}` - Encrypt PDF at path
- `GET /pdf_encryptor.php?share={id}` - Access shared encrypted PDF
- `GET /pdf_encryptor.php?share={id}&raw=1` - Raw encrypted data (for JS client)

### JavaScript Classes

```javascript
const decryptor = new PdfDecryptor();

// Decrypt PDF data
const decrypted = await decryptor.decryptPdf(encryptedData, keyHex);

// Load and display PDF
await decryptor.loadPdfFromData(decryptedData);
await decryptor.renderPage(pageNumber, container);

// Navigation
await decryptor.nextPage();
await decryptor.prevPage();
await decryptor.goToPage(pageNumber);
```

## PDF.js Integration

The system uses PDF.js library for client-side PDF rendering:

- **Version**: 3.11.174 (via CDN)
- **Features**: Page-by-page rendering, zoom, navigation
- **Performance**: Efficient memory usage for large PDFs
- **Compatibility**: Works with all PDF versions

## Development

1. Place all files in web server document root
2. Ensure PHP OpenSSL extension is enabled
3. Access via HTTP (localhost for development)
4. Use `test.php` for testing and examples

## Examples

### Encrypt a PDF
```bash
# Via URL
curl "http://localhost/PdfEnc/pdf_encryptor.php/C:/path/to/document.pdf"

# Via query parameter
curl "http://localhost/PdfEnc/pdf_encryptor.php?pdf=/path/to/document.pdf"
```

### Decrypt and View
1. Open `http://localhost/PdfEnc/decrypt.html`
2. Upload the generated `.bin` file
3. Enter the decryption key
4. Navigate through PDF pages

## Troubleshooting

### Common Issues

**"Failed to load PDF"**
- Check if decryption key is correct
- Verify encrypted file is not corrupted
- Ensure PDF.js library loads properly

**"Page rendering failed"**
- Try refreshing the page
- Check browser console for errors
- Ensure PDF is not password-protected originally

**"Navigation not working"**
- Verify PDF has multiple pages
- Check browser compatibility
- Ensure PDF.js worker is loading

## License

This project demonstrates AES-CTR PDF encryption for educational purposes.

## Related Projects

- **ImgEnc**: Image encryption system using same algorithm
- **AES-GCM**: General purpose encryption toolkit
