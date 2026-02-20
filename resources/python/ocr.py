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
    month_pattern = r"(?:JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|SEPT|OCT|NOV|DEC)"
    marker_pattern = r"(?:Ref(?:erence)?(?:\s*[\.:])?\s*(?:No\.?|Number)?|Transaction\s*(?:ID|No\.?))"
    marker = re.search(marker_pattern, text, re.IGNORECASE)
    if marker:
        # Include following chars so wrapped reference digits on the next line are considered.
        tail = text[marker.end(): marker.end() + 180]
        raw = tail.upper()
        raw = re.sub(rf"\b{month_pattern}\b\s+\d{{1,2}},?\s+\d{{4}}", " ", raw)
        raw = re.sub(r"\b\d{1,2}:\d{2}\b", " ", raw)
        raw = re.sub(r"\bAM\b|\bPM\b", " ", raw)
        raw = re.sub(r"[^A-Z0-9\s\-]", " ", raw)

        digit_groups = re.findall(r"\d{3,}", raw)
        if digit_groups:
            if len(digit_groups[0]) >= 10:
                return digit_groups[0]

            candidate = "".join(digit_groups[:3])
            if 6 <= len(candidate) <= 16:
                return candidate

    patterns = [
        r"(?:Ref(?:erence)?(?:\s*[\.:])?\s*(?:No\.?|Number)?\s*[:\-]?\s*)([A-Z0-9][A-Z0-9\-\s]{5,})",
        r"(?:Transaction\s*(?:ID|No\.?)\s*[:\-]?\s*)([A-Z0-9\-]{6,})",
    ]
    for pattern in patterns:
        match = re.search(pattern, text, re.IGNORECASE)
        if match:
            raw = match.group(1).upper().strip()
            raw = re.split(rf"\b{month_pattern}\b|\b\d{{1,2}}:\d{{2}}\b|\bAM\b|\bPM\b", raw, maxsplit=1)[0]
            candidate = re.sub(r"[^A-Z0-9\-]", "", raw)
            if len(candidate) >= 6:
                return candidate
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
