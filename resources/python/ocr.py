# Import necessary libraries
import pytesseract  # For OCR (Optical Character Recognition)
import cv2  # OpenCV for image processing
import re  # Regular expressions for text parsing
import sys  # System-specific parameters and functions
import json  # For JSON output
import os  # Environment variables

tesseract_cmd = os.getenv("TESSERACT_CMD")
if tesseract_cmd:
    pytesseract.pytesseract.tesseract_cmd = tesseract_cmd

try:
    cv2.setLogLevel(0)
except Exception:
    pass

def extract_gcash_info(image_path):
    """
    Extract payment information from a GCash receipt image using OCR.

    Args:
        image_path (str): Path to the receipt image file

    Returns:
        dict: Extracted data including text, amount, reference, and confidence,
              or error message if processing fails
    """
    try:
        # Load the image from the given path
        image = cv2.imread(image_path)
        if image is None:
            return {"error": "Image not found"}

        # Convert to grayscale for better OCR accuracy
        gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)

        # Apply thresholding to create a binary image (black and white)
        # This helps improve OCR accuracy on receipts
        _, thresh = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)

        # Perform OCR on the processed image
        text = pytesseract.image_to_string(thresh, lang='eng')

        # Extract amount using regex pattern (looks for PHP prefix or just numbers with decimals)
        amount_match = re.search(r'(?:PHP\s*)?(\d+(?:\.\d{2})?)', text)
        amount = float(amount_match.group(1)) if amount_match else None

        # Extract reference number using regex (looks for "Ref No", "Reference", etc.)
        ref_match = re.search(r'(?:Ref(?:erence)?\s*(?:No\.?|Number)?:?\s*)(\w+)', text, re.IGNORECASE)
        reference = ref_match.group(1) if ref_match else None

        # Set a default confidence score (in a real implementation, this could be calculated)
        confidence = 0.8

        # Return the extracted information
        return {
            "text": text,  # Full OCR text
            "amount": amount,  # Extracted payment amount
            "reference": reference,  # Extracted reference number
            "confidence": confidence  # OCR confidence score
        }
    except Exception as e:
        # Return error information if anything goes wrong
        return {"error": str(e)}

# Main execution block (runs when script is called directly)
if __name__ == "__main__":
    # Check if exactly one argument (image path) is provided
    if len(sys.argv) != 2:
        print(json.dumps({"error": "Usage: python ocr.py <image_path>"}))
        sys.exit(1)

    # Extract information from the provided image
    result = extract_gcash_info(sys.argv[1])

    # Output the result as JSON
    print(json.dumps(result))
