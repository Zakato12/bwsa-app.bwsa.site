#!/usr/bin/env python3
"""
Simple OCR CLI using pytesseract and OpenCV/Pillow.
Usage: python try.py path/to/image.jpg
"""
import argparse
import os
import sys
from PIL import Image
import cv2
import numpy as np
import pytesseract

def find_tesseract_exe():
	# Common Windows path
	possible = [
		r"C:\Program Files\Tesseract-OCR\tesseract.exe",
		r"C:\Program Files (x86)\Tesseract-OCR\tesseract.exe",
	]
	for p in possible:
		if os.path.exists(p):
			return p
	return None

def preprocess_image_cv(path, method="thresh"):
	img = cv2.imread(path)
	if img is None:
		raise FileNotFoundError(path)
	gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
	if method == "thresh":
		gray = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY | cv2.THRESH_OTSU)[1]
	elif method == "blur":
		gray = cv2.medianBlur(gray, 3)
	return gray

def ocr_image(path, lang=None, preprocess="thresh"):
	# If PIL is needed for certain image formats, convert via PIL
	if preprocess:
		img = preprocess_image_cv(path, preprocess)
		pil_img = Image.fromarray(img)
	else:
		pil_img = Image.open(path)
	config = ""
	text = pytesseract.image_to_string(pil_img, lang=lang, config=config)
	return text

def main():
	ap = argparse.ArgumentParser(description="Simple OCR CLI")
	ap.add_argument("image", help="Path to image")
	ap.add_argument("--lang", help="Tesseract language (e.g., eng)", default=None)
	ap.add_argument("--preprocess", help="Preprocess method: thresh|blur|none", default="thresh")
	ap.add_argument("--tesseract-cmd", help="Path to tesseract executable", default=None)
	args = ap.parse_args()

	if args.tesseract_cmd:
		pytesseract.pytesseract.tesseract_cmd = args.tesseract_cmd
	else:
		# Auto-detect common Windows install
		if sys.platform.startswith("win"):
			exe = find_tesseract_exe()
			if exe:
				pytesseract.pytesseract.tesseract_cmd = exe

	try:
		text = ocr_image(args.image, lang=args.lang, preprocess=(None if args.preprocess=="none" else args.preprocess))
	except Exception as e:
		print("Error:", e, file=sys.stderr)
		sys.exit(2)

	print("---- OCR OUTPUT ----")
	print(text)

if __name__ == "__main__":
	main()

