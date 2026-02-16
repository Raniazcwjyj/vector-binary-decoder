"""FastAPI service wrapping vector decoder engine core."""

from __future__ import annotations

import os
import re
import time
import uuid
from pathlib import Path
from typing import Any

from fastapi import Depends, FastAPI, Header, HTTPException, Request
from fastapi.responses import JSONResponse
from pydantic import BaseModel, Field

try:
    from .engine_core import EngineError, capture_and_decode
except ImportError:  # pragma: no cover - allows direct module run
    from engine_core import EngineError, capture_and_decode


APP_TITLE = "Vector Decoder Internal Engine API"
INTERNAL_TOKEN = os.getenv("INTERNAL_API_TOKEN", "change-me")
WORK_DIR = Path(os.getenv("ENGINE_WORK_DIR", "./engine_output")).resolve()
WORK_DIR.mkdir(parents=True, exist_ok=True)

URL_PATTERN = re.compile(r"^https://([a-z0-9-]+\.)*vectorizer\.ai(/.*)?$", re.IGNORECASE)


class CaptureConvertRequest(BaseModel):
    task_id: str = Field(min_length=1)
    url: str | None = None
    image_path: str | None = None
    width: int = Field(default=400, ge=1, le=4096)
    height: int = Field(default=400, ge=1, le=4096)
    channel: str = Field(default="auto")
    headless: bool = Field(default=True)
    max_wait_seconds: int = Field(default=120, ge=1, le=300)
    idle_seconds: int = Field(default=3, ge=1, le=20)
    verbose: bool = Field(default=False)


class CaptureConvertResponse(BaseModel):
    ok: bool
    task_id: str
    output_file: str | None = None
    svg: str | None = None
    meta: dict[str, Any] | None = None
    error_code: str | None = None
    message: str | None = None
    details: str | None = None
    elapsed_ms: int | None = None


def _logger(task_id: str):
    def _log(msg: str) -> None:
        print(f"[{task_id}] {msg}", flush=True)

    return _log


def verify_internal_token(x_internal_token: str | None = Header(default=None)) -> None:
    if not x_internal_token or x_internal_token != INTERNAL_TOKEN:
        raise HTTPException(status_code=401, detail="Invalid internal token.")


def _validate_target_url(url: str) -> None:
    if not URL_PATTERN.match(url):
        raise HTTPException(status_code=400, detail="URL must match https://*.vectorizer.ai/*")


app = FastAPI(title=APP_TITLE, version="1.0.0")


@app.exception_handler(EngineError)
async def handle_engine_error(_: Request, exc: EngineError):
    status = 500
    if exc.code == "E_PARAM":
        status = 400
    elif exc.code == "E_TIMEOUT":
        status = 408
    elif exc.code in ("E_NO_PALETTE", "E_NO_CHUNK"):
        status = 422
    elif exc.code == "E_BROWSER_LAUNCH":
        status = 502

    return JSONResponse(
        status_code=status,
        content={
            "ok": False,
            "error_code": exc.code,
            "message": exc.message,
            "details": exc.details,
        },
    )


@app.get("/internal/v1/health")
async def health(_: None = Depends(verify_internal_token)):
    return {
        "ok": True,
        "service": APP_TITLE,
        "work_dir": str(WORK_DIR),
    }


@app.post("/internal/v1/capture-convert", response_model=CaptureConvertResponse)
def capture_convert(req: CaptureConvertRequest, _: None = Depends(verify_internal_token)):
    image_path: str | None = None
    if req.image_path:
        image = Path(req.image_path).expanduser().resolve()
        if not image.exists() or not image.is_file():
            raise HTTPException(status_code=400, detail="image_path is not a valid file.")
        image_path = str(image)
    elif req.url:
        _validate_target_url(req.url)
    else:
        raise HTTPException(status_code=400, detail="Either url or image_path is required.")

    task_id = req.task_id or str(uuid.uuid4())
    task_dir = WORK_DIR / task_id
    task_dir.mkdir(parents=True, exist_ok=True)
    output_path = task_dir / "output.svg"

    started = time.perf_counter()
    result = capture_and_decode(
        url=req.url,
        output_dir=str(task_dir),
        output_file=str(output_path),
        image_path=image_path,
        width=req.width,
        height=req.height,
        channel=req.channel,
        headless=req.headless,
        max_wait_seconds=req.max_wait_seconds,
        idle_seconds=req.idle_seconds,
        verbose=req.verbose,
        logger=_logger(task_id),
    )
    elapsed_ms = int((time.perf_counter() - started) * 1000)

    payload = result.to_dict()
    meta = payload.get("meta", {})
    meta["elapsed_ms"] = elapsed_ms

    return CaptureConvertResponse(
        ok=True,
        task_id=task_id,
        output_file=payload["output_file"],
        svg=payload["svg"],
        meta=meta,
        elapsed_ms=elapsed_ms,
    )
