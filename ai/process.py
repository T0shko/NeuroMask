import sys
import os

os.environ["PYTHONIOENCODING"] = "utf-8"
os.environ["TQDM_DISABLE"] = "1"
os.environ["HF_HOME"] = r"E:\NeuroMaskAI\hf_cache"
os.environ["INSIGHTFACE_HOME"] = r"E:\NeuroMaskAI\insightface"

import cv2
import numpy as np
import argparse
import traceback
import time

# --- MONKEY PATCH FOR BASICSR ON NEW PYTORCH ---
try:
    import torchvision.transforms.functional_tensor
except ImportError:
    try:
        import torchvision.transforms.functional as tv_f
        sys.modules['torchvision.transforms.functional_tensor'] = tv_f
    except ImportError:
        pass

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
SWAPPER_MODEL_NAME = "inswapper_128.onnx"
GFPGAN_MODEL_NAME = "GFPGANv1.4.pth"
REALESRGAN_MODEL_NAME = "RealESRGAN_x2plus.pth"
FACE_ANALYSIS_MODEL = "buffalo_l"
DET_SIZE = (640, 640)
PROVIDERS = ["CUDAExecutionProvider", "CPUExecutionProvider"]
OUTPUT_QUALITY = 95
MAX_IMAGE_DIM = 2048

DEBUG_MODE = False

def log_info(msg: str) -> None:
    print(f"[INFO] {msg}", flush=True)

def log_warning(msg: str) -> None:
    print(f"[WARNING] {msg}", flush=True)

def log_error(msg: str) -> None:
    print(f"[ERROR] {msg}", file=sys.stderr, flush=True)

def load_image(filepath: str, label: str) -> np.ndarray:
    if not os.path.isfile(filepath):
        raise FileNotFoundError(f"{label} file not found: {filepath}")
    img = cv2.imread(filepath)
    if img is None:
        raise ValueError(f"Cannot decode {label} image: {filepath}")
    h, w = img.shape[:2]
    if max(h, w) > MAX_IMAGE_DIM:
        scale = MAX_IMAGE_DIM / max(h, w)
        img = cv2.resize(img, (int(w * scale), int(h * scale)), interpolation=cv2.INTER_AREA)
    return img

def save_image(img: np.ndarray, filepath: str) -> None:
    out_dir = os.path.dirname(os.path.abspath(filepath))
    os.makedirs(out_dir, exist_ok=True)
    ok = cv2.imwrite(filepath, img, [cv2.IMWRITE_JPEG_QUALITY, OUTPUT_QUALITY])
    if not ok:
        raise IOError(f"Failed to write output image: {filepath}")

class NeuroSwapEngine:
    def __init__(self):
        self.face_analyzer = None
        self.swapper = None
        self.gfpgan = None
        self.realesrgan = None
        self.codeformer = None
        
    def initialize_models(self) -> None:
        import insightface
        log_info(f"Initializing InsightFace ({FACE_ANALYSIS_MODEL})...")
        self.face_analyzer = insightface.app.FaceAnalysis(name=FACE_ANALYSIS_MODEL, providers=PROVIDERS)
        self.face_analyzer.prepare(ctx_id=0, det_size=DET_SIZE)

        model_path = os.path.join(SCRIPT_DIR, SWAPPER_MODEL_NAME)
        if not os.path.isfile(model_path):
            raise FileNotFoundError(f"Missing swapper model: {model_path}")
        self.swapper = insightface.model_zoo.get_model(model_path, providers=PROVIDERS)
        
        # Load GFPGAN (for Fast Mode)
        try:
            from gfpgan import GFPGANer
            gfp_path = os.path.join(SCRIPT_DIR, GFPGAN_MODEL_NAME)
            if os.path.isfile(gfp_path):
                self.gfpgan = GFPGANer(model_path=gfp_path, upscale=1, arch='clean', channel_multiplier=2)
                log_info("GFPGAN loaded (Fast Mode).")
        except Exception as e:
            log_warning(f"GFPGAN could not be loaded: {e}")

        # Load RealESRGAN
        self.realesrgan = None
        self.realesrgan_hq = None
        try:
            from realesrgan import RealESRGANer
            from basicsr.archs.rrdbnet_arch import RRDBNet
            
            # Standard 2x Upscaler
            realesrgan_path = os.path.join(SCRIPT_DIR, REALESRGAN_MODEL_NAME)
            if os.path.isfile(realesrgan_path):
                model = RRDBNet(num_in_ch=3, num_out_ch=3, num_feat=64, num_block=23, num_grow_ch=32, scale=2)
                self.realesrgan = RealESRGANer(scale=2, model_path=realesrgan_path, model=model, tile=400, tile_pad=10, pre_pad=0, half=True)
                log_info("RealESRGAN x2 loaded.")
                
            # HQ 4x Upscaler
            realesrgan_hq_path = os.path.join(SCRIPT_DIR, "RealESRGAN_x4plus.pth")
            if os.path.isfile(realesrgan_hq_path):
                model_hq = RRDBNet(num_in_ch=3, num_out_ch=3, num_feat=64, num_block=23, num_grow_ch=32, scale=4)
                self.realesrgan_hq = RealESRGANer(scale=4, model_path=realesrgan_hq_path, model=model_hq, tile=400, tile_pad=10, pre_pad=0, half=True)
                log_info("RealESRGAN x4 (HQ) loaded.")
        except Exception as e:
            log_warning(f"RealESRGAN could not be loaded: {e}")

    def detect_face(self, image: np.ndarray, label: str):
        faces = self.face_analyzer.get(image)
        if not faces:
            raise ValueError(f"No face detected in {label} image.")
        return max(faces, key=lambda f: (f.bbox[2] - f.bbox[0]) * (f.bbox[3] - f.bbox[1]))

    def process_fast(self, source_img: np.ndarray, target_img: np.ndarray) -> np.ndarray:
        log_info("Executing FAST Mode...")
        source_face = self.detect_face(source_img, "source")
        target_face = self.detect_face(target_img, "target")

        log_info("Swapping face...")
        result = self.swapper.get(target_img.copy(), target_face, source_face, paste_back=True)

        if self.gfpgan:
            log_info("Enhancing with GFPGAN...")
            _, _, result = self.gfpgan.enhance(result, has_aligned=False, only_center_face=False, paste_back=True)

        if self.realesrgan:
            log_info("Upscaling background with RealESRGAN...")
            result, _ = self.realesrgan.enhance(result, outscale=2)

        return result

    def process_hq(self, source_img: np.ndarray, target_img: np.ndarray) -> np.ndarray:
        log_info("Executing HQ Mode...")
        source_face = self.detect_face(source_img, "source")
        target_face = self.detect_face(target_img, "target")

        log_info("Pass 1: Swapping face...")
        result = self.swapper.get(target_img.copy(), target_face, source_face, paste_back=True)

        log_info("Pass 2: Tone correction + source texture transfer...")
        import torch
        from facexlib.utils.face_restoration_helper import FaceRestoreHelper
        from skimage.exposure import match_histograms

        device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')

        source_helper = FaceRestoreHelper(upscale_factor=1, face_size=512, crop_ratio=(1, 1), det_model='retinaface_resnet50', save_ext='png', use_parse=False, device=device)
        source_helper.read_image(source_img)
        source_helper.get_face_landmarks_5(only_center_face=True, eye_dist_threshold=5)
        source_helper.align_warp_face()
        source_crop = source_helper.cropped_faces[0] if source_helper.cropped_faces else None

        # Target face crop — used as the lighting/tone reference
        target_helper = FaceRestoreHelper(upscale_factor=1, face_size=512, crop_ratio=(1, 1), det_model='retinaface_resnet50', save_ext='png', use_parse=False, device=device)
        target_helper.read_image(target_img)
        target_helper.get_face_landmarks_5(only_center_face=True, eye_dist_threshold=5)
        target_helper.align_warp_face()
        target_crop_ref = target_helper.cropped_faces[0] if target_helper.cropped_faces else None

        face_helper = FaceRestoreHelper(upscale_factor=1, face_size=512, crop_ratio=(1, 1), det_model='retinaface_resnet50', save_ext='png', use_parse=True, device=device)
        face_helper.read_image(result)
        face_helper.get_face_landmarks_5(only_center_face=False, eye_dist_threshold=5)
        face_helper.align_warp_face()

        if source_crop is not None:
            tone_ref = target_crop_ref if target_crop_ref is not None else face_helper.cropped_faces[0]
            for idx, ai_crop in enumerate(face_helper.cropped_faces):
                # Correct inswapper brightness to match target portrait lighting
                ai_corrected = np.clip(match_histograms(ai_crop, tone_ref, channel_axis=-1), 0, 255).astype(np.uint8)

                # 101px blur: only sub-101px detail (beard stubble, eyebrow hairs) in high-pass
                source_matched = np.clip(match_histograms(source_crop, tone_ref, channel_axis=-1), 0, 255).astype(np.uint8)
                blur_kernel = (101, 101)
                low_pass_ai = cv2.GaussianBlur(ai_corrected, blur_kernel, 0).astype(np.float32)
                low_pass_source = cv2.GaussianBlur(source_matched, blur_kernel, 0).astype(np.float32)
                high_pass_source = source_matched.astype(np.float32) - low_pass_source

                # Strip color: prevents RGB artifacts in B&W output
                hp_gray = cv2.cvtColor(
                    np.clip(high_pass_source + 128, 0, 255).astype(np.uint8),
                    cv2.COLOR_BGR2GRAY
                ).astype(np.float32) - 128
                high_pass_source = cv2.merge([hp_gray, hp_gray, hp_gray])

                h, w = high_pass_source.shape[:2]
                Y, X = np.ogrid[:h, :w]
                cx, cy = w // 2, h // 2
                weight = np.exp(-0.5 * (((X - cx) / (w * 0.42))**2 + ((Y - cy) / (h * 0.48))**2))
                high_pass_source = high_pass_source * weight[:, :, np.newaxis]

                texture_restored = low_pass_ai + high_pass_source
                texture_restored = (
                    texture_restored * weight[:, :, np.newaxis] +
                    ai_corrected.astype(np.float32) * (1 - weight[:, :, np.newaxis])
                )
                texture_restored = np.clip(texture_restored, 0, 255).astype(np.uint8)
                face_helper.add_restored_face(texture_restored)

            face_helper.get_inverse_affine(None)
            result = face_helper.paste_faces_to_input_image(save_path=None, upsample_img=result)

        # GFPGAN at 0.5: cleans inswapper artifacts
        if self.gfpgan:
            log_info("Pass 3: GFPGAN cleanup (weight=0.5)...")
            try:
                _, _, result = self.gfpgan.enhance(result, has_aligned=False, only_center_face=False, paste_back=True, weight=0.5)
            except TypeError:
                _, _, result = self.gfpgan.enhance(result, has_aligned=False, only_center_face=False, paste_back=True)

        # Pass 4: Brightness match — force face region to match target's original tonal distribution
        log_info("Pass 4: Face brightness match...")
        bbox = target_face.bbox.astype(int)
        pad_x = int((bbox[2] - bbox[0]) * 0.35)
        pad_y_top = int((bbox[3] - bbox[1]) * 0.5)
        pad_y_bot = int((bbox[3] - bbox[1]) * 0.35)
        x1 = max(0, bbox[0] - pad_x)
        y1 = max(0, bbox[1] - pad_y_top)
        x2 = min(result.shape[1], bbox[2] + pad_x)
        y2 = min(result.shape[0], bbox[3] + pad_y_bot)
        if x2 > x1 and y2 > y1:
            result_roi = result[y1:y2, x1:x2].copy()
            target_roi = target_img[y1:y2, x1:x2]
            matched_roi = np.clip(match_histograms(result_roi, target_roi, channel_axis=-1), 0, 255).astype(np.uint8)
            rh, rw = matched_roi.shape[:2]
            fy = max(8, rh // 5)
            fx = max(8, rw // 5)
            mask = np.ones((rh, rw), dtype=np.float32)
            ramp_y = np.linspace(0, 1, fy)
            ramp_x = np.linspace(0, 1, fx)
            mask[:fy, :] *= ramp_y[:, None]
            mask[-fy:, :] *= ramp_y[::-1][:, None]
            mask[:, :fx] *= ramp_x[None, :]
            mask[:, -fx:] *= ramp_x[::-1][None, :]
            mask = cv2.GaussianBlur(mask, (31, 31), 0)[..., None]
            blended = matched_roi.astype(np.float32) * mask + result_roi.astype(np.float32) * (1 - mask)
            result[y1:y2, x1:x2] = np.clip(blended, 0, 255).astype(np.uint8)

        if self.realesrgan_hq:
            log_info("Pass 5: Upscaling with RealESRGAN x4...")
            result, _ = self.realesrgan_hq.enhance(result, outscale=4)
        elif self.realesrgan:
            log_info("Pass 5: Upscaling with RealESRGAN x2 (Fallback)...")
            result, _ = self.realesrgan.enhance(result, outscale=2)

        log_info("Pass 6: Sharpening...")
        blur = cv2.GaussianBlur(result, (0, 0), 3)
        result = cv2.addWeighted(result, 1.8, blur, -0.8, 0)

        return result

def main() -> int:
    parser = argparse.ArgumentParser(description="NeuroMask Face Swap Engine")
    parser.add_argument("source_path", help="Source face image")
    parser.add_argument("target_path", help="Target image (body/scene)")
    parser.add_argument("output_path", help="Output file path")
    parser.add_argument("--mode", help="Processing mode: fast or hq", default="fast")
    parser.add_argument("--debug", action="store_true", help="Save intermediate debug images")
    args = parser.parse_args()

    global DEBUG_MODE
    DEBUG_MODE = args.debug

    try:
        engine = NeuroSwapEngine()
        engine.initialize_models()

        source_img = load_image(args.source_path, "source")
        target_img = load_image(args.target_path, "target")

        t0 = time.time()
        if args.mode.lower() == "hq":
            final_img = engine.process_hq(source_img, target_img)
        else:
            final_img = engine.process_fast(source_img, target_img)
        
        log_info(f"Total execution: {time.time() - t0:.2f}s")
        save_image(final_img, args.output_path)
        return 0
    except Exception as e:
        log_error(str(e))
        traceback.print_exc(file=sys.stderr)
        return 1

if __name__ == "__main__":
    sys.exit(main())
