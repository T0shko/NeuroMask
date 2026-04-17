from diffusers import StableDiffusionInpaintPipeline
import torch

try:
    pipe = StableDiffusionInpaintPipeline.from_pretrained(
        "runwayml/stable-diffusion-inpainting",
        torch_dtype=torch.float16
    )
    print("Inpaint pipeline loaded")
    pipe.load_ip_adapter("h94/IP-Adapter-FaceID", subfolder=None, weight_name="ip-adapter-faceid_sd15.bin")
    print("IP Adapter FaceID loaded successfully!")
except Exception as e:
    print(f"Error: {e}")
