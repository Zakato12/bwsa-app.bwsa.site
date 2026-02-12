import json
import os
import re
import subprocess
import sys


def extract_amount(text):
    # Prefer explicit amount keywords first.
    patterns = [
        r"(?:Amount|Total|Paid)\s*[:\-]?\s*(?:PHP|Php|P)?\s*([0-9]+(?:\.[0-9]{2})?)",
        r"(?:PHP|Php|P)\s*([0-9]+(?:\.[0-9]{2})?)",
    ]
    for pattern in patterns:
        match = re.search(pattern, text, re.IGNORECASE)
        if match:
            try:
                return float(match.group(1))
            except Exception:
                pass

    generic = re.search(r"([0-9]+(?:\.[0-9]{2})?)", text)
    if generic:
        try:
            return float(generic.group(1))
        except Exception:
            return None
    return None


def extract_reference(text):
    patterns = [
        r"(?:Ref(?:erence)?\s*(?:No\.?|Number)?\s*[:\-]?\s*)([A-Z0-9\-]{6,})",
        r"(?:Transaction\s*(?:ID|No\.?)\s*[:\-]?\s*)([A-Z0-9\-]{6,})",
    ]
    for pattern in patterns:
        match = re.search(pattern, text, re.IGNORECASE)
        if match:
            return match.group(1)
    return None


def run_tesseract(image_path):
    tesseract_cmd = os.getenv("TESSERACT_CMD", "tesseract")
    cmd = [tesseract_cmd, image_path, "stdout", "-l", "eng"]

    try:
        proc = subprocess.run(
            cmd,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            universal_newlines=True,
            check=False,
        )
    except FileNotFoundError:
        return None, "tesseract binary not found"
    except Exception as exc:
        return None, str(exc)

    if proc.returncode != 0:
        err = proc.stderr.strip() or "tesseract failed"
        return None, err

    return proc.stdout or "", None


def extract_gcash_info(image_path):
    if not os.path.isfile(image_path):
        return {"error": "Image not found"}

    text, err = run_tesseract(image_path)
    if err:
        return {"error": err}

    amount = extract_amount(text)
    reference = extract_reference(text)

    return {
        "text": text,
        "amount": amount,
        "reference": reference,
        "confidence": 0.7,
    }


if __name__ == "__main__":
    if len(sys.argv) != 2:
        print(json.dumps({"error": "Usage: python ocr.py <image_path>"}))
        sys.exit(1)

    result = extract_gcash_info(sys.argv[1])
    print(json.dumps(result))
