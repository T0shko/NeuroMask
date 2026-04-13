"""
Neuromax – AI Face Swap Engine (Dual-Mode: Fast & HQ Diffusion)
===============================================================

Mode 1 [FAST]: InsightFace Matrix Swap + GFPGAN (Sub 5 seconds)
Mode 2 [HQ]: Generative Diffusers IP-Adapter Face Inpainting (Ultimate 1:1 Redraw)

Usage:
    python process.py <source_path> <target_path> <output_path> [--mode hq|fast] [--debug]
"""

import sys
import os

# Reroute massive tensor downloads to the D: drive immediately to stop C: exhaustion
os.environ["HF_HOME"] = r"D:\NeuroMaskAI\hf_cache"
os.environ["INSIGHTFACE_HOME"] = r"D:\NeuroMaskAI\insightface"

import time
import argparse
import traceback
import numpy as np
import cv2

# --- MONKEY PATCH FOR BASICSR ON NEW PYTORCH ---
try:
    import torchvision.transforms.functional_tensor
except ImportError:
    try:
        import torchvision.transforms.functional as tv_f
        sys.modules['torchvision.transforms.functional_tensor'] = tv_f
    except ImportError:
        pass
# -----------------------------------------------

# ============================================================
# CONSTANTS & GLOBALS
# ============================================================

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
SWAPPER_MODEL_NAME = "inswapper_128.onnx"
GFPGAN_MODEL_NAME = "GFPGANv1.4.pth"
REALESRGAN_MODEL_NAME = "RealESRGAN_x2plus.pth"
FACE_ANALYSIS_MODEL = "buffalo_l"
DET_SIZE = (640, 640)
PROVIDERS = ["CUDAExecutionProvider"]
OUTPUT_QUALITY = 95
MAX_IMAGE_DIM = 2048

DEBUG_MODE = False

# ============================================================
# LOGGING
# ============================================================

def log_info(message: str) -> None:
    print(f"[INFO] {message}", flush=True)

def log_error(message: str) -> None:
    print(f"[ERROR] {message}", file=sys.stderr, flush=True)

def log_warning(message: str) -> None:
    print(f"[WARNING] {message}", flush=True)

# ============================================================
# IMAGE I/O
# ============================================================

def load_image(filepath: str, label: str) -> np.ndarray:
    if not os.path.isfile(filepath):
        raise FileNotFoundError(f"{label.capitalize()} file not found: {filepath}")
    image = cv2.imread(filepath)
    if image is None:
        raise ValueError(f"Cannot decode {label} image: {filepath}.")

    h, w = image.shape[:2]
    if max(h, w) > MAX_IMAGE_DIM:
        scale = MAX_IMAGE_DIM / max(h, w)
        image = cv2.resize(image, (int(w * scale), int(h * scale)), interpolation=cv2.INTER_AREA)
    return image

def save_image(image: np.ndarray, filepath: str) -> None:
    output_dir = os.path.dirname(os.path.abspath(filepath))
    if output_dir and not os.path.isdir(output_dir):
        os.makedirs(output_dir, exist_ok=True)
    success = cv2.imwrite(filepath, image, [cv2.IMWRITE_JPEG_QUALITY, OUTPUT_QUALITY])
    if not success:
        raise IOError("Failed to push final frame to disk.")

# ============================================================
# UTILITIES
# ============================================================

def get_face_inpaint_mask(image_shape, face, margin_ratio=0.5):
    """
    Creates a dilated binary mask covering the face and immediate jaw boundaries
    for Generative Inpainting.
    """
    mask = np.zeros(image_shape[:2], dtype=np.uint8)
    bbox = face.bbox.astype(int)
    w, h = bbox[2] - bbox[0], bbox[3] - bbox[1]

    margin_w = int(w * margin_ratio)
    margin_h = int(h * margin_ratio)

    x1 = max(0, bbox[0] - margin_w)
    y1 = max(0, bbox[1] - margin_h)
    x2 = min(image_shape[1], bbox[2] + margin_w)
    y2 = min(image_shape[0], bbox[3] + margin_h)

    # Draw soft convex hull to prevent boxing artifacts
    if hasattr(face, 'landmark_2d_106') and face.landmark_2d_106 is not None:
        kps = face.landmark_2d_106.astype(np.int32)
        hull = cv2.convexHull(kps)
        cv2.fillConvexPoly(mask, hull, 255)
        # Dilate mask aggressively to cover jawline for diffusion
        kernel = cv2.getStructuringElement(cv2.MORPH_ELLIPSE, (margin_w, margin_h))
        mask = cv2.dilate(mask, kernel, iterations=1)
    else:
        cv2.ellipse(mask, (int((x1+x2)/2), int((y1+y2)/2)), (int(w*1.2), int(h*1.2)), 0, 0, 360, 255, -1)

    return mask

def match_colors_soft(source_img: np.ndarray, target_img: np.ndarray, mask: np.ndarray) -> np.ndarray:
    mask_soft = cv2.GaussianBlur(mask, (31, 31), 0).astype(np.float32) / 255.0
    mask_soft = np.repeat(mask_soft[:, :, np.newaxis], 3, axis=2)
    return (source_img * mask_soft + target_img * (1 - mask_soft)).astype(np.uint8)

# ============================================================
# CORE ENGINE
# ============================================================

class NeuroSwapEngine:
    def __init__(self):
        self._face_analyzer = None
        self._swapper = None
        self._gfpgan = None

        # Diffusion Assets
        self.diffusion_pipe = None

    def initialize_insightface(self):
        import insightface
        from insightface.app import FaceAnalysis

        log_info(f"Waking up InsightFace Analysis ({FACE_ANALYSIS_MODEL}) on {PROVIDERS[0]}")
        self._face_analyzer = FaceAnalysis(name=FACE_ANALYSIS_MODEL, providers=PROVIDERS)
        self._face_analyzer.prepare(ctx_id=0, det_size=DET_SIZE)

    def load_fast_models(self):
        import insightface
        model_path = os.path.join(SCRIPT_DIR, SWAPPER_MODEL_NAME)
        if not os.path.isfile(model_path):
            raise FileNotFoundError(f"Missing {SWAPPER_MODEL_NAME}")
        self._swapper = insightface.model_zoo.get_model(model_path, providers=PROVIDERS)

        # GFPGAN
        try:
            from gfpgan import GFPGANer
            gfpgan_path = os.path.join(SCRIPT_DIR, GFPGAN_MODEL_NAME)
            self._gfpgan = GFPGANer(model_path=gfpgan_path, upscale=1, arch='clean', channel_multiplier=2)
        except Exception as e:
            log_error(f"GFPGAN instantiation fault: {e}")

    def load_diffusion_models(self):
        import torch
        from diffusers import StableDiffusionControlNetInpaintPipeline, ControlNetModel

        log_info("Deploying High-Quality Diffusion Transformers (10GB+ VRAM architecture optimized for 8GB via Xformers)...")
        # Ensure we construct the inpainting controlnet perfectly.
        controlnet = ControlNetModel.from_pretrained(
            "lllyasviel/control_v11p_sd15_inpaint",
            torch_dtype=torch.float16
        )

        pipe = StableDiffusionControlNetInpaintPipeline.from_pretrained(
            "SG161222/Realistic_Vision_V5.1_noVAE",
            controlnet=controlnet,
            torch_dtype=torch.float16,
            safety_checker=None
        ).to("cuda")

        log_info("Mounting IP-Adapter-FaceID Projections...")
        # FaceID requires no image encoder natively because it feeds off raw 512 dim ArcFace values.
        pipe.load_ip_adapter(
            "h94/IP-Adapter-FaceID",
            subfolder="",
            weight_name="ip-adapter-faceid_sd15.bin",
            image_encoder_folder=None
        )

        # Optimize memory usage for 8GB RTX 3070 to avoid CUDA OOM bounds
        try:
            pipe.enable_xformers_memory_efficient_attention()
            log_info("Xformers Tensor Cores engaged.")
        except Exception:
            pipe.enable_attention_slicing()
            log_warning("Xformers missing. Falling back to Sliced Attention (Slower execution).")

        self.diffusion_pipe = pipe

        # We also need GFPGAN to sharpen up the generated pores natively
        try:
            from gfpgan import GFPGANer
            gfpgan_path = os.path.join(SCRIPT_DIR, GFPGAN_MODEL_NAME)
            if os.path.isfile(gfpgan_path):
                self._gfpgan = GFPGANer(model_path=gfpgan_path, upscale=1, arch='clean', channel_multiplier=2)
        except:
            pass

    def detect_face(self, image: np.ndarray, label: str):
        faces = self._face_analyzer.get(image)
        if not faces:
            raise ValueError(f"No face detected in {label} image.")
        return sorted(faces, key=lambda f: (f.bbox[2]-f.bbox[0])*(f.bbox[3]-f.bbox[1]), reverse=True)[0]

    def mode_fast_process(self, source_img: np.ndarray, target_img: np.ndarray) -> np.ndarray:
        source_face = self.detect_face(source_img, "source")
        target_face = self.detect_face(target_img, "target")

        log_info("Executing Inswapper Multi-Pass routine...")
        swap_pass = self._swapper.get(target_img.copy(), target_face, source_face, paste_back=True)
        inter_face = self.detect_face(swap_pass, "intermediate")
        swap_pass = self._swapper.get(swap_pass, inter_face, source_face, paste_back=True)

        if self._gfpgan:
             log_info("Capping GFPGAN to preserve underlying structure mapping...")
             _, _, enhanced_img = self._gfpgan.enhance(swap_pass, has_aligned=False, only_center_face=False, paste_back=True, weight=0.6)
             if enhanced_img is not None:
                 swap_pass = enhanced_img
        return swap_pass

    def mode_hq_diffusion_process(self, source_img: np.ndarray, target_img: np.ndarray) -> np.ndarray:
        import torch
        from PIL import Image

        source_face = self.detect_face(source_img, "source")
        target_face = self.detect_face(target_img, "target")

        # ── 1. Create Diffusers Environment State ──
        log_info("Entering Diffusion Canvas... Preparing geometry tensors.")
        mask_np = get_face_inpaint_mask(target_img.shape, target_face, margin_ratio=0.45)

        # Convert states to PIL
        init_image = Image.fromarray(cv2.cvtColor(target_img, cv2.COLOR_BGR2RGB))
        mask_image = Image.fromarray(mask_np)

        # Diffusers ControlNet Inpaint requires the control image to be structurally formatted with the mask.
        control_image = Image.fromarray(cv2.cvtColor(target_img, cv2.COLOR_BGR2RGB))

        # ── 2. Structural Identity Extraction ──
        log_info("Extracting pure 512-Dim Identity Vector map...")
        # IP-Adapter requires shape (1, 1, 512) for face embeds
        face_emb = source_face.normed_embedding
        face_emb_torch = torch.tensor(face_emb, dtype=torch.float16).unsqueeze(0).unsqueeze(0)

        # ── 3. High-Quality Diffusion Hallucination ──
        log_info(f"Redrawing Identity 1:1 using Checkpoint Integration... Rendering...")
        torch.cuda.empty_cache() # Clear 3070 VRAM bounds

        generator = torch.Generator(device="cuda").manual_seed(42) # Consistent outputs

        # The prompt forces photorealistic pores and identical human flesh reconstruction
        prompt = "photorealistic face, raw photograph, 8k resolution, ultra detailed pores, sharp focus, perfectly illuminated skin, cinematic lighting"
        neg_prompt = "cartoon, 3d, artificial, plastic, smooth skin, doll, deformed, malformed skull, weird lighting, extra fingers, blurry, low res, oversaturated"

        # Execution loop natively commands SD1.5 to overwrite the inside of the mask natively
        # using IP-Adapter embeds.
        diffused_out = self.diffusion_pipe(
            prompt=prompt,
            negative_prompt=neg_prompt,
            image=init_image,
            mask_image=mask_image,
            control_image=control_image,
            ip_adapter_image_embeds=[face_emb_torch], # Target Identity Injection
            num_inference_steps=30,  # Optimal speed/quality on RTX cards
            guidance_scale=6.5,      # High adherence to identity
            generator=generator,
            strength=0.95            # How much to deviate from original pixels (95% redrawn)
        ).images[0]

        diffused_bgr = cv2.cvtColor(np.array(diffused_out), cv2.COLOR_RGB2BGR)

        # ── 4. Generative Compositing ──
        log_info("Generative pass complete. Passing to AI refiner...")

        # We natively rely on the Diffusers VAE to stitch the Deepfake border seamlessly.
        # No manual alpha blurring to prevent the "Halo" effect.
        final_merged = diffused_bgr

        if self._gfpgan:
            log_info("Final pass: Generative pixel polishing via GAN...")
            _, _, enhanced_img = self._gfpgan.enhance(final_merged, has_aligned=False, only_center_face=False, paste_back=True, weight=0.65)
            if enhanced_img is not None:
                final_merged = enhanced_img

        return final_merged


def process(source_path: str, target_path: str, output_path: str, mode: str) -> None:
    engine = NeuroSwapEngine()
    engine.initialize_insightface()

    if mode == 'hq':
        # Fallback to fast mode safely if the heavy models fail to mount
        try:
            engine.load_diffusion_models()
        except ImportError as e:
            log_error(f"Diffusion pip modules completely missing. Please heavily run install_dependencies.bat. {e}. Falling back to FAST mode.")
            mode = 'fast'
        except Exception as e:
            log_error(f"Diffusion HQ VRAM architecture failed. VRAM Overflow? Exception: {e}. Falling back to FAST mode.")
            mode = 'fast'

    if mode == 'fast':
        engine.load_fast_models()

    source_img = load_image(source_path, "source")
    target_img = load_image(target_path, "target")

    start_time = time.time()
    if mode == 'hq':
        final_img = engine.mode_hq_diffusion_process(source_img, target_img)
    else:
        final_img = engine.mode_fast_process(source_img, target_img)

    log_info(f"Engine execution ({mode.upper()}) completed in {time.time() - start_time:.2f} seconds.")
    save_image(final_img, output_path)

def main() -> int:
    global DEBUG_MODE
    parser = argparse.ArgumentParser()
    parser.add_argument("source_path")
    parser.add_argument("target_path")
    parser.add_argument("output_path")
    parser.add_argument("--mode", choices=['fast', 'hq'], default='hq', help="Select generative pipeline intensity.")
    parser.add_argument("--debug", action="store_true")

    args = parser.parse_args()
    if args.debug:
        DEBUG_MODE = True

    try:
        process(args.source_path, args.target_path, args.output_path, args.mode)
        return 0
    except Exception as e:
        log_error(str(e))
        traceback.print_exc(file=sys.stderr)
        return 1

if __name__ == "__main__":
    sys.exit(main())
