#!/usr/bin/env python3
"""
Prepare company logo for professional use: trim padding, transparent background,
sharpen, and export standard sizes at 300 DPI.
"""

from __future__ import annotations

import os
import sys
from pathlib import Path

from PIL import Image, ImageChops, ImageFilter

ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "uploads" / "settings" / "d07d112af6259a42f70ef4d4cf343594.png"
OUT_DIR = ROOT / "uploads" / "settings" / "branding"

WHITE_THRESHOLD = 248
PADDING_TRIM = 2
CANVAS_MARGIN_RATIO = 0.04
DPI = (300, 300)


def is_near_white(r: int, g: int, b: int, a: int) -> bool:
    if a < 8:
        return True
    return r >= WHITE_THRESHOLD and g >= WHITE_THRESHOLD and b >= WHITE_THRESHOLD


def trim_and_transparent(img: Image.Image) -> Image.Image:
    rgba = img.convert("RGBA")
    pixels = rgba.load()
    width, height = rgba.size

    for y in range(height):
        for x in range(width):
            r, g, b, a = pixels[x, y]
            if is_near_white(r, g, b, a):
                pixels[x, y] = (r, g, b, 0)

    alpha = rgba.split()[-1]
    bbox = alpha.getbbox()
    if not bbox:
        raise RuntimeError("Logo appears empty after trimming.")

    left = max(0, bbox[0] - PADDING_TRIM)
    top = max(0, bbox[1] - PADDING_TRIM)
    right = min(width, bbox[2] + PADDING_TRIM)
    bottom = min(height, bbox[3] + PADDING_TRIM)
    return rgba.crop((left, top, right, bottom))


def trim_for_quote_header(img: Image.Image, target_height: int = 88) -> Image.Image:
    rgba = img.convert("RGBA")
    pixels = rgba.load()
    width, height = rgba.size

    for y in range(height):
        for x in range(width):
            r, g, b, a = pixels[x, y]
            if is_near_white(r, g, b, a):
                continue
            if r < 55 and g < 55 and b < 55:
                pixels[x, y] = (r, g, b, 0)

    alpha = rgba.split()[-1]
    bbox = alpha.getbbox()
    if not bbox:
        raise RuntimeError("Quote header logo appears empty after trimming.")

    cropped = rgba.crop(bbox)
    if cropped.height > target_height:
        scale = target_height / cropped.height
        new_w = max(1, int(round(cropped.width * scale)))
        cropped = cropped.resize((new_w, target_height), Image.Resampling.LANCZOS)
    return cropped


def sharpen(img: Image.Image) -> Image.Image:
    return img.filter(ImageFilter.UnsharpMask(radius=1.2, percent=165, threshold=2))


def fit_on_square_canvas(img: Image.Image, canvas_size: int) -> Image.Image:
    margin = max(8, int(canvas_size * CANVAS_MARGIN_RATIO))
    max_w = canvas_size - (margin * 2)
    max_h = canvas_size - (margin * 2)
    scale = min(max_w / img.width, max_h / img.height)
    new_w = max(1, int(round(img.width * scale)))
    new_h = max(1, int(round(img.height * scale)))
    resized = img.resize((new_w, new_h), Image.Resampling.LANCZOS)
    canvas = Image.new("RGBA", (canvas_size, canvas_size), (0, 0, 0, 0))
    x = (canvas_size - new_w) // 2
    y = (canvas_size - new_h) // 2
    canvas.paste(resized, (x, y), resized)
    return canvas


def scale_to_width(img: Image.Image, target_width: int) -> Image.Image:
    scale = target_width / img.width
    new_h = max(1, int(round(img.height * scale)))
    return img.resize((target_width, new_h), Image.Resampling.LANCZOS)


def save_png(img: Image.Image, path: Path) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    img.save(path, format="PNG", optimize=True, dpi=DPI)


def main() -> int:
    source = Path(sys.argv[1]) if len(sys.argv) > 1 else SOURCE
    if not source.is_file():
        print("Source logo not found:", source, file=sys.stderr)
        return 1

    img = Image.open(source)
    master = sharpen(trim_and_transparent(img))

    save_png(master, OUT_DIR / "sateri-digital-logo-transparent.png")
    save_png(fit_on_square_canvas(master, 512), OUT_DIR / "sateri-digital-logo-512.png")
    save_png(fit_on_square_canvas(master, 1024), OUT_DIR / "sateri-digital-logo-1024.png")
    save_png(scale_to_width(master, 2000), OUT_DIR / "sateri-digital-logo-2000w.png")

    quote_header = sharpen(trim_for_quote_header(img))
    save_png(quote_header, OUT_DIR / "sateri-digital-logo-quote-header.png")

    print("Prepared logo assets in:", OUT_DIR)
    for name in sorted(OUT_DIR.glob("sateri-digital-logo-*.png")):
        with Image.open(name) as out:
            print(f"  {name.name}: {out.size[0]}x{out.size[1]} px, mode={out.mode}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
