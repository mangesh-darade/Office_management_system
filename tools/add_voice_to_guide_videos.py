#!/usr/bin/env python3
"""Add English voice narration to user-guide WebM videos (edge-tts + ffmpeg)."""
from __future__ import annotations

import asyncio
import json
import os
import subprocess
import sys
import tempfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
VID = ROOT / "docs/user-guide/videos"
NARR = ROOT / "docs/user-guide/narrations.json"


def ffmpeg_exe() -> str:
    try:
        import imageio_ffmpeg

        return imageio_ffmpeg.get_ffmpeg_exe()
    except ImportError:
        pass
    for name in ("ffmpeg", "ffmpeg.exe"):
        from shutil import which

        p = which(name)
        if p:
            return p
    raise RuntimeError("ffmpeg not found — pip install imageio-ffmpeg")


async def tts_to_mp3(text: str, out_mp3: Path, voice: str, rate: str) -> None:
    import edge_tts

    communicate = edge_tts.Communicate(text, voice, rate=rate)
    await communicate.save(str(out_mp3))


def merge_video_audio(video: Path, audio: Path, out: Path, ff: str) -> None:
    cmd = [
        ff,
        "-y",
        "-i",
        str(video),
        "-i",
        str(audio),
        "-c:v",
        "libvpx-vp9",
        "-crf",
        "32",
        "-b:v",
        "0",
        "-c:a",
        "libopus",
        "-shortest",
        str(out),
    ]
    subprocess.run(cmd, check=True, capture_output=True)


def main() -> int:
    if not NARR.is_file():
        print(f"Missing {NARR}")
        return 2

    cfg = json.loads(NARR.read_text(encoding="utf-8"))
    voice = cfg.get("voice", "en-IN-NeerjaNeural")
    rate = cfg.get("rate", "+0%")
    tracks: dict[str, str] = cfg.get("tracks", {})

    try:
        ff = ffmpeg_exe()
    except RuntimeError as exc:
        print(exc)
        return 2

    ok = skip = 0

    async def run_all() -> None:
        nonlocal ok, skip
        for rel, text in tracks.items():
            src = VID / rel.replace("/", os.sep)
            if not src.is_file():
                print(f"  SKIP missing video: {rel}")
                skip += 1
                continue
            out = src.with_name(src.stem + "-voiced.webm")
            tmp = Path(tempfile.mkdtemp(prefix="guide_tts_"))
            mp3 = tmp / "narration.mp3"
            try:
                await tts_to_mp3(text.strip(), mp3, voice, rate)
                merge_video_audio(src, mp3, out, ff)
                print(f"  OK  {out.relative_to(ROOT)}")
                ok += 1
            except Exception as exc:
                print(f"  SKIP {rel}: {exc}")
                skip += 1
            finally:
                import shutil

                shutil.rmtree(tmp, ignore_errors=True)

    asyncio.run(run_all())
    print(f"\nDone — {ok} voiced, {skip} skipped")
    return 0 if ok else 1


if __name__ == "__main__":
    sys.exit(main())
