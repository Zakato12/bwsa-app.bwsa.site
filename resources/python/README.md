# Simple OCR (resources/python)

Requirements:
- Python 3.8+
- Tesseract OCR engine installed on your system (not the Python package). On Windows, install from an installer such as the UB Mannheim build or the official releases.

Install Python packages:

```bash
pip install -r resources/python/requirements.txt
```

Windows note: If Tesseract is not on your PATH, pass its executable path with `--tesseract-cmd` or install to `C:\Program Files\Tesseract-OCR\`.

Run the CLI:

```bash
python resources/python/try.py path/to/image.jpg
# optional flags: --preprocess thresh|blur|none  --lang eng  --tesseract-cmd "C:\Program Files\Tesseract-OCR\tesseract.exe"
```

The script prints recognized text to stdout.
