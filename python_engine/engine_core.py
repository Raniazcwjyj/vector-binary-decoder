"""Core capture/parse/render engine for vector-binary-decoder service."""

from __future__ import annotations

import base64
import hashlib
import json
import math
import os
import struct
import time
from dataclasses import dataclass
from pathlib import Path
from typing import Callable


Logger = Callable[[str], None] | None


class EngineError(Exception):
    """Structured engine exception with stable error code."""

    def __init__(self, code: str, message: str, details: str | None = None):
        super().__init__(message)
        self.code = code
        self.message = message
        self.details = details


def _emit_log(msg: str, verbose: bool = True, logger: Logger = None) -> None:
    if not verbose:
        return
    if logger is not None:
        logger(msg)


class BinaryReader:
    def __init__(self, data: bytes):
        self.data = data
        self.offset = 0

    def read_int8(self) -> int:
        val = struct.unpack_from(">b", self.data, self.offset)[0]
        self.offset += 1
        return val

    def read_int32(self) -> int:
        val = struct.unpack_from(">i", self.data, self.offset)[0]
        self.offset += 4
        return val

    def read_float32(self) -> float:
        val = struct.unpack_from(">f", self.data, self.offset)[0]
        self.offset += 4
        return val

    def read_boolean(self) -> bool:
        return self.read_int8() != 0


def read_curve_without_cursor(reader: BinaryReader):
    curve_type = reader.read_int8()
    if curve_type == 0:
        start_x = reader.read_float32()
        start_y = reader.read_float32()
        end_x = reader.read_float32()
        end_y = reader.read_float32()
        return {"type": "line", "start": (start_x, start_y), "end": (end_x, end_y)}
    if curve_type == 1:
        start_x = reader.read_float32()
        start_y = reader.read_float32()
        ctrl_x = reader.read_float32()
        ctrl_y = reader.read_float32()
        end_x = reader.read_float32()
        end_y = reader.read_float32()
        return {
            "type": "quadratic",
            "start": (start_x, start_y),
            "control": (ctrl_x, ctrl_y),
            "end": (end_x, end_y),
        }
    if curve_type == 2:
        start_x = reader.read_float32()
        start_y = reader.read_float32()
        ctrl1_x = reader.read_float32()
        ctrl1_y = reader.read_float32()
        ctrl2_x = reader.read_float32()
        ctrl2_y = reader.read_float32()
        end_x = reader.read_float32()
        end_y = reader.read_float32()
        return {
            "type": "cubic",
            "start": (start_x, start_y),
            "control1": (ctrl1_x, ctrl1_y),
            "control2": (ctrl2_x, ctrl2_y),
            "end": (end_x, end_y),
        }
    if curve_type in (3, 4):
        is_large_arc = reader.read_int8() != 0
        is_clockwise = reader.read_int8() != 0
        start_x = reader.read_float32()
        start_y = reader.read_float32()
        center_x = reader.read_float32()
        center_y = reader.read_float32()
        radius = reader.read_float32()
        theta_start = reader.read_float32()
        delta_theta = reader.read_float32()
        end_x = reader.read_float32()
        end_y = reader.read_float32()
        return {
            "type": "arc",
            "start": (start_x, start_y),
            "center": (center_x, center_y),
            "radius": radius,
            "theta_start": theta_start,
            "delta_theta": delta_theta,
            "is_large_arc": is_large_arc,
            "is_clockwise": is_clockwise,
            "end": (end_x, end_y),
        }
    if curve_type in (5, 6):
        is_large_arc = reader.read_int8() != 0
        is_clockwise = reader.read_int8() != 0
        start_x = reader.read_float32()
        start_y = reader.read_float32()
        center_x = reader.read_float32()
        center_y = reader.read_float32()
        radius_x = reader.read_float32()
        radius_y = reader.read_float32()
        rotation = reader.read_float32()
        theta_start = reader.read_float32()
        delta_theta = reader.read_float32()
        end_x = reader.read_float32()
        end_y = reader.read_float32()
        return {
            "type": "ellipse_arc",
            "start": (start_x, start_y),
            "center": (center_x, center_y),
            "radius_x": radius_x,
            "radius_y": radius_y,
            "rotation": rotation,
            "theta_start": theta_start,
            "delta_theta": delta_theta,
            "is_large_arc": is_large_arc,
            "is_clockwise": is_clockwise,
            "end": (end_x, end_y),
        }
    raise ValueError(f"Unknown curve type: {curve_type}")


def read_curve_array(reader: BinaryReader):
    num_curves = reader.read_int32()
    return [read_curve_without_cursor(reader) for _ in range(num_curves)]


def read_vector_loop(reader: BinaryReader):
    loop_type = reader.read_int8()
    index = reader.read_int32()
    vector_shape_index = reader.read_int32()
    is_positive = reader.read_boolean()
    loop = {
        "type": loop_type,
        "index": index,
        "vector_shape_index": vector_shape_index,
        "is_positive": is_positive,
    }
    if loop_type == 1:
        loop["center_x"] = reader.read_float32()
        loop["center_y"] = reader.read_float32()
        loop["radius"] = reader.read_float32()
    elif loop_type == 2:
        loop["center_x"] = reader.read_float32()
        loop["center_y"] = reader.read_float32()
        loop["radius_x"] = reader.read_float32()
        loop["radius_y"] = reader.read_float32()
        loop["rotation"] = reader.read_float32()
    elif loop_type == 3:
        loop["center_x"] = reader.read_float32()
        loop["center_y"] = reader.read_float32()
        loop["half_width"] = reader.read_float32()
        loop["half_height"] = reader.read_float32()
        loop["corner_radius"] = reader.read_float32()
        loop["rotation"] = reader.read_float32()
    loop["curves"] = read_curve_array(reader)
    return loop


def read_vector_shape(reader: BinaryReader):
    index = reader.read_int32()
    parent_loop_index = reader.read_int32()
    palette_index = reader.read_int32()
    return {"index": index, "parent_loop_index": parent_loop_index, "palette_index": palette_index}


def read_vector_interface(reader: BinaryReader):
    parent_vector_loop_index = reader.read_int32()
    vector_loop_index0 = reader.read_int32()
    vector_loop_index1 = reader.read_int32()
    argb_int = reader.read_int32()
    curves = read_curve_array(reader)
    return {
        "parent_vector_loop_index": parent_vector_loop_index,
        "vector_loop_index0": vector_loop_index0,
        "vector_loop_index1": vector_loop_index1,
        "argb_int": argb_int,
        "curves": curves,
    }


def parse_binary_data(data: bytes):
    reader = BinaryReader(data)
    num_shapes = reader.read_int32()
    shapes = [read_vector_shape(reader) for _ in range(num_shapes)]
    num_loops = reader.read_int32()
    loops = [read_vector_loop(reader) for _ in range(num_loops)]
    num_interfaces = reader.read_int32()
    interfaces = [read_vector_interface(reader) for _ in range(num_interfaces)]
    return {"shapes": shapes, "loops": loops, "interfaces": interfaces}


def merge_parsed_data(data_list: list[dict]) -> dict:
    merged = {"shapes": [], "loops": [], "interfaces": []}
    for data in data_list:
        merged["shapes"].extend(data["shapes"])
        merged["loops"].extend(data["loops"])
        merged["interfaces"].extend(data["interfaces"])
    merged["shapes"].sort(key=lambda x: x["index"])
    merged["loops"].sort(key=lambda x: x["index"])
    return merged


def curve_to_svg_path(curve: dict, is_first: bool = False) -> str:
    path = ""
    if is_first:
        path += f"M {curve['start'][0]:.2f} {curve['start'][1]:.2f} "

    if curve["type"] == "line":
        path += f"L {curve['end'][0]:.2f} {curve['end'][1]:.2f} "
    elif curve["type"] == "quadratic":
        ctrl = curve["control"]
        end = curve["end"]
        path += f"Q {ctrl[0]:.2f} {ctrl[1]:.2f} {end[0]:.2f} {end[1]:.2f} "
    elif curve["type"] == "cubic":
        c1 = curve["control1"]
        c2 = curve["control2"]
        end = curve["end"]
        path += f"C {c1[0]:.2f} {c1[1]:.2f} {c2[0]:.2f} {c2[1]:.2f} {end[0]:.2f} {end[1]:.2f} "
    elif curve["type"] in ("arc", "ellipse_arc"):
        if curve["type"] == "arc":
            rx = ry = curve["radius"]
            rotation = 0.0
        else:
            rx = curve["radius_x"]
            ry = curve["radius_y"]
            rotation = math.degrees(curve["rotation"])
        large_arc = 1 if curve["is_large_arc"] else 0
        sweep = 1 if curve["is_clockwise"] else 0
        end = curve["end"]
        path += f"A {rx:.2f} {ry:.2f} {rotation:.2f} {large_arc} {sweep} {end[0]:.2f} {end[1]:.2f} "
    return path


def curves_to_svg_path(curves: list[dict]) -> str:
    if not curves:
        return ""
    parts = []
    for i, curve in enumerate(curves):
        parts.append(curve_to_svg_path(curve, is_first=(i == 0)))
    parts.append("Z")
    return "".join(parts)


def build_palette_map(palette_message: dict) -> dict[int, dict]:
    body = palette_message.get("body", palette_message)
    palette_obj = body.get("userPalette") or body.get("palette") or {}
    colors = palette_obj.get("colors", [])
    palette = {}
    for color in colors:
        try:
            index = int(color["index"])
        except Exception:
            continue
        palette[index] = {"css": color.get("css", "#000000"), "opacity": color.get("opacity", 1)}
    return palette


def generate_svg(parsed_data: dict, palette: dict, width: int = 400, height: int = 400) -> str:
    min_x = min_y = float("inf")
    max_x = max_y = float("-inf")

    for loop in parsed_data["loops"]:
        for curve in loop["curves"]:
            for key in ("start", "end"):
                if key in curve:
                    x, y = curve[key]
                    min_x = min(min_x, x)
                    min_y = min(min_y, y)
                    max_x = max(max_x, x)
                    max_y = max(max_y, y)

    if min_x == float("inf"):
        view_min_x = 0
        view_min_y = 0
        view_width = max(width, 1)
        view_height = max(height, 1)
    else:
        padding = 10
        view_min_x = min_x - padding
        view_min_y = min_y - padding
        view_width = (max_x - min_x) + 2 * padding
        view_height = (max_y - min_y) + 2 * padding

    svg_parts = [
        f'<svg xmlns="http://www.w3.org/2000/svg" width="{width}" height="{height}" '
        f'viewBox="{view_min_x:.2f} {view_min_y:.2f} {view_width:.2f} {view_height:.2f}">'
    ]

    shape_loops = {}
    for loop in parsed_data["loops"]:
        shape_idx = loop["vector_shape_index"]
        shape_loops.setdefault(shape_idx, []).append(loop)

    for shape in parsed_data["shapes"]:
        shape_idx = shape["index"]
        palette_idx = shape["palette_index"]
        color_info = palette.get(palette_idx, {"css": "#808080", "opacity": 1})
        color = color_info["css"]
        opacity = color_info["opacity"]
        if opacity == 0:
            continue
        for loop in shape_loops.get(shape_idx, []):
            path_d = curves_to_svg_path(loop["curves"])
            if not path_d:
                continue
            fill_rule = "evenodd" if not loop["is_positive"] else "nonzero"
            opacity_attr = f' fill-opacity="{opacity}"' if opacity < 1 else ""
            svg_parts.append(
                f'<path d="{path_d}" fill="{color}"{opacity_attr} fill-rule="{fill_rule}" stroke="none"/>'
            )

    svg_parts.append("</svg>")
    return "\n".join(svg_parts)


def _decode_ws_binary_payload(payload: str) -> bytes | None:
    data = "".join(payload.split())
    if not data:
        return None
    padding = (-len(data)) % 4
    if padding:
        data += "=" * padding
    try:
        return base64.b64decode(data, validate=False)
    except Exception:
        return None


def _extract_palette_message(text: str) -> dict | None:
    try:
        payload = json.loads(text)
    except Exception:
        return None
    if not isinstance(payload, dict):
        return None
    body = payload.get("body", payload)
    if not isinstance(body, dict):
        return None
    palette_obj = body.get("userPalette") or body.get("palette")
    if not isinstance(palette_obj, dict):
        return None
    colors = palette_obj.get("colors")
    if not isinstance(colors, list) or not colors:
        return None
    return payload


def _resolve_launch_candidates(channel: str) -> list[str | None]:
    requested = (channel or "").strip().lower()

    if requested in ("", "auto"):
        candidates = [None, "msedge", "chrome"]
    elif requested == "chromium":
        candidates = [None, "msedge", "chrome"]
    elif requested in ("chrome", "msedge"):
        fallback = "msedge" if requested == "chrome" else "chrome"
        candidates = [requested, None, fallback]
    else:
        candidates = [requested, None, "msedge", "chrome"]

    deduped: list[str | None] = []
    for c in candidates:
        if c not in deduped:
            deduped.append(c)
    return deduped


def _launch_browser_with_fallback(p, headless: bool, channel: str, verbose: bool, logger: Logger = None):
    candidates = _resolve_launch_candidates(channel)
    errors = []

    base_args = []
    # Server-safe defaults (especially for root/systemd environments).
    if os.name == "posix":
        base_args.extend(
            [
                "--no-sandbox",
                "--disable-setuid-sandbox",
                "--disable-dev-shm-usage",
                "--disable-gpu",
            ]
        )

    for c in candidates:
        label = c if c else "bundled-chromium"
        # If headful mode fails, auto-fallback to headless for robustness.
        headless_attempts = [headless] if headless else [False, True]

        for mode in headless_attempts:
            kwargs = {"headless": mode, "args": base_args}
            if c:
                kwargs["channel"] = c
            try:
                _emit_log(f"[LAUNCH] trying {label}, headless={mode}...", verbose, logger)
                browser = p.chromium.launch(**kwargs)
                _emit_log(f"[LAUNCH] started with {label}, headless={mode}", verbose, logger)
                return browser, f"{label}, headless={mode}"
            except Exception as exc:
                full_err = (str(exc) or "").strip()
                short_err = full_err.splitlines()[0] if full_err else repr(exc)
                detail_err = " | ".join(line.strip() for line in full_err.splitlines()[:6]) if full_err else short_err
                errors.append(f"{label}, headless={mode}: {detail_err}")
                _emit_log(f"[LAUNCH] failed {label}, headless={mode}: {detail_err}", True, logger)

    raise EngineError(
        "E_BROWSER_LAUNCH",
        "Browser launch failed for all candidates.",
        details="\n".join(errors),
    )


@dataclass
class CaptureResult:
    output_file: str
    svg: str
    chunks_used: int
    chunks_total: int
    duplicates_skipped: int
    shapes: int
    loops: int
    interfaces: int
    palette_colors: int

    def to_dict(self) -> dict:
        return {
            "output_file": self.output_file,
            "svg": self.svg,
            "meta": {
                "chunks_used": self.chunks_used,
                "chunks_total": self.chunks_total,
                "duplicates_skipped": self.duplicates_skipped,
                "shapes": self.shapes,
                "loops": self.loops,
                "interfaces": self.interfaces,
                "palette_colors": self.palette_colors,
            },
        }


def capture_and_decode(
    url: str | None,
    output_dir: str,
    output_file: str,
    image_path: str | None = None,
    width: int = 400,
    height: int = 400,
    channel: str = "auto",
    headless: bool = True,
    max_wait_seconds: int = 120,
    idle_seconds: int = 3,
    verbose: bool = False,
    logger: Logger = None,
) -> CaptureResult:
    if width <= 0 or height <= 0:
        raise EngineError("E_PARAM", "width and height must be positive integers.")
    if max_wait_seconds <= 0:
        raise EngineError("E_PARAM", "max_wait_seconds must be positive.")
    if idle_seconds <= 0:
        raise EngineError("E_PARAM", "idle_seconds must be positive.")
    if not url and not image_path:
        raise EngineError("E_PARAM", "Either url or image_path must be provided.")
    if image_path:
        image_file = Path(image_path).expanduser().resolve()
        if not image_file.exists() or not image_file.is_file():
            raise EngineError("E_PARAM", f"image_path does not exist: {image_file}")
    else:
        image_file = None

    out_dir = Path(output_dir).resolve()
    out_dir.mkdir(parents=True, exist_ok=True)
    output_svg = Path(output_file).resolve()

    try:
        from playwright.sync_api import sync_playwright
    except Exception as exc:
        raise EngineError("E_INTERNAL", "Playwright is required.", details=str(exc)) from exc

    state = {"palette": None, "last_data_at": time.monotonic(), "socket_urls": {}}
    unique_chunks: dict[str, bytes] = {}
    duplicates_skipped = 0

    with sync_playwright() as p:
        browser, launch_used = _launch_browser_with_fallback(
            p=p,
            headless=headless,
            channel=channel,
            verbose=verbose,
            logger=logger,
        )
        _emit_log(f"[LAUNCH] active: {launch_used}", verbose, logger)
        context = browser.new_context()
        page = context.new_page()
        cdp = context.new_cdp_session(page)
        cdp.send("Network.enable")

        def on_ws_created(params):
            request_id = params.get("requestId")
            ws_url = params.get("url")
            if request_id and ws_url:
                state["socket_urls"][request_id] = ws_url
                _emit_log(f"[WS] created: {ws_url}", verbose, logger)

        def on_ws_frame_received(params):
            nonlocal duplicates_skipped

            request_id = params.get("requestId")
            frame = params.get("response", {})
            opcode = frame.get("opcode")
            payload_data = frame.get("payloadData", "")

            if opcode == 1:
                message = _extract_palette_message(payload_data)
                if message is not None:
                    body = message.get("body", message)
                    palette_obj = body.get("userPalette") or body.get("palette") or {}
                    color_count = len(palette_obj.get("colors", []))
                    state["palette"] = message
                    state["last_data_at"] = time.monotonic()
                    ws_url = state["socket_urls"].get(request_id, "<unknown>")
                    _emit_log(f"[PALETTE] captured from {ws_url}, colors={color_count}", verbose, logger)
                return

            if opcode == 2:
                raw = _decode_ws_binary_payload(payload_data)
                if not raw:
                    return
                digest = hashlib.sha256(raw).hexdigest()
                if digest in unique_chunks:
                    duplicates_skipped += 1
                    return
                try:
                    parse_binary_data(raw)
                except Exception:
                    return
                unique_chunks[digest] = raw
                state["last_data_at"] = time.monotonic()
                ws_url = state["socket_urls"].get(request_id, "<unknown>")
                _emit_log(f"[CHUNK] captured parseable binary, bytes={len(raw)}, ws={ws_url}", verbose, logger)

        cdp.on("Network.webSocketCreated", on_ws_created)
        cdp.on("Network.webSocketFrameReceived", on_ws_frame_received)
        if image_file is not None:
            target_url = "https://vectorizer.ai/"
            _emit_log(f"[NAV] goto upload page: {target_url}", verbose, logger)
            page.goto(target_url, wait_until="domcontentloaded")
            try:
                page.wait_for_selector("input[type='file']", timeout=20_000, state="attached")
            except Exception as exc:
                raise EngineError(
                    "E_PARAM",
                    "Unable to find file upload input on vectorizer.ai.",
                    details=str(exc),
                ) from exc

            uploaded = False
            upload_error = None
            locators = page.locator("input[type='file']")
            count = locators.count()
            _emit_log(f"[UPLOAD] file input count={count}", verbose, logger)

            # Some pages contain multiple file inputs; test each candidate.
            for idx in range(max(count, 1)):
                try:
                    if count > 0:
                        locators.nth(idx).set_input_files(str(image_file))
                        _emit_log(f"[UPLOAD] set files on input[{idx}]", verbose, logger)
                    else:
                        page.set_input_files("input[type='file']", str(image_file))
                        _emit_log("[UPLOAD] set files via generic selector", verbose, logger)

                    # If websocket starts shortly after upload, this input is likely correct.
                    signal = False
                    for _ in range(20):
                        page.wait_for_timeout(300)
                        if state["socket_urls"] or state["palette"] is not None or unique_chunks:
                            signal = True
                            break

                    if signal:
                        uploaded = True
                        _emit_log(f"[UPLOAD] websocket signal detected after input[{idx}]", verbose, logger)
                        break

                    # Keep first successful upload as fallback even if signal is delayed.
                    if not uploaded:
                        uploaded = True
                except Exception as exc:
                    upload_error = exc

            if not uploaded:
                raise EngineError(
                    "E_PARAM",
                    "Failed to upload image to vectorizer.ai.",
                    details=str(upload_error) if upload_error else None,
                )
            _emit_log(f"[UPLOAD] image queued: {image_file.name}", verbose, logger)
        else:
            _emit_log(f"[NAV] goto result url: {url}", verbose, logger)
            page.goto(str(url), wait_until="domcontentloaded")

        start = time.monotonic()
        while True:
            page.wait_for_timeout(200)
            now = time.monotonic()
            elapsed = now - start
            idle = now - state["last_data_at"]

            if state["palette"] is not None and unique_chunks and idle >= idle_seconds:
                _emit_log(
                    f"Stable data reached. idle={idle:.1f}s, palette=1, chunks={len(unique_chunks)}.",
                    verbose,
                    logger,
                )
                break

            if elapsed >= max_wait_seconds:
                if state["palette"] is not None and unique_chunks:
                    _emit_log(
                        f"[TIMEOUT] max_wait reached ({max_wait_seconds}s), using partial capture chunks={len(unique_chunks)}",
                        True,
                        logger,
                    )
                    break
                raise EngineError(
                    "E_TIMEOUT",
                    f"Capture timeout after {max_wait_seconds}s.",
                    details=f"palette_found={state['palette'] is not None}, chunks_found={len(unique_chunks)}",
                )

        browser.close()

    if state["palette"] is None:
        raise EngineError("E_NO_PALETTE", "No palette message captured from websocket.")
    if not unique_chunks:
        raise EngineError("E_NO_CHUNK", "No parseable binary chunk captured from websocket.")

    palette_path = out_dir / "palette.json"
    palette_path.write_text(json.dumps(state["palette"], ensure_ascii=False, indent=2), encoding="utf-8")

    parsed_list = []
    for idx, raw in enumerate(unique_chunks.values(), start=1):
        (out_dir / f"chunk{idx}.bin").write_bytes(raw)
        parsed_list.append(parse_binary_data(raw))

    palette = build_palette_map(state["palette"])
    if not palette:
        raise EngineError("E_NO_PALETTE", "Palette captured but no usable color entries found.")

    merged = merge_parsed_data(parsed_list) if len(parsed_list) > 1 else parsed_list[0]
    svg = generate_svg(merged, palette, width=width, height=height)
    output_svg.write_text(svg, encoding="utf-8")

    return CaptureResult(
        output_file=str(output_svg),
        svg=svg,
        chunks_used=len(unique_chunks),
        chunks_total=len(unique_chunks),
        duplicates_skipped=duplicates_skipped,
        shapes=len(merged["shapes"]),
        loops=len(merged["loops"]),
        interfaces=len(merged["interfaces"]),
        palette_colors=len(palette),
    )
