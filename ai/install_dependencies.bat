@echo off
echo =========================================================
echo Neuromax AI - Face Swap Dependency Installer for Windows
echo =========================================================
echo.
echo This script will install all required Python packages
echo for the AI Face Swap feature.
echo.

:: Check if Python is installed
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Python is not installed or not in your system PATH!
    echo Please install Python 3.10, 3.11, or 3.12 and try again.
    pause
    exit /b 1
)

echo [INFO] Updating pip to the latest version...
python -m pip install --upgrade pip

echo [INFO] Restructuring machine environment for CUDA GPU Acceleration (RTX 3070)...
python -m pip uninstall -y torch torchvision onnxruntime
python -m pip install torch torchvision --index-url https://download.pytorch.org/whl/cu124

echo.
echo [INFO] Installing Microsoft Visual C++ Build Tools (Required for compiling AI Models)...
echo [INFO] Downloading installer...
if not exist "vs_buildtools.exe" (
    powershell -Command "Invoke-WebRequest -Uri 'https://aka.ms/vs/17/release/vs_buildtools.exe' -OutFile 'vs_buildtools.exe'"
)
echo [INFO] Launching MSVC Installer. Please click "Yes" on the Administrator prompt!
echo [INFO] This will install the C++ Workloads required by InsightFace/xFormers.
start /wait vs_buildtools.exe --passive --wait --norestart --nocache --add Microsoft.VisualStudio.Workload.VCTools --includeRecommended
echo [SUCCESS] Visual C++ Build Tools configured!
echo.

echo [INFO] Installing remaining required dependencies...
python -m pip install -r requirements.txt

echo.
echo [INFO] PRE-CACHING SD1.5 INPAINTING MODELS (~4GB total — do NOT close this window)...
echo [INFO]   - Lykon/dreamshaper-8-inpainting (SD1.5 photorealistic inpainting base, ~3GB)
echo [INFO]   - h94/IP-Adapter-FaceID (identity adapter and LoRA, ~0.2GB)
python -c "import os; os.environ['HF_HOME']=r'E:\NeuroMaskAI\hf_cache'; from huggingface_hub import hf_hub_download, snapshot_download; print('Downloading DreamShaper Inpainting...'); snapshot_download(repo_id='Lykon/dreamshaper-8-inpainting'); print('Downloading IP-Adapter FaceID and LoRA...'); hf_hub_download(repo_id='h94/IP-Adapter-FaceID', filename='ip-adapter-faceid_sd15.bin', local_dir=r'E:\NeuroMaskAI\hf_cache\instantid', local_dir_use_symlinks=False); hf_hub_download(repo_id='h94/IP-Adapter-FaceID', filename='ip-adapter-faceid_sd15_lora.safetensors', local_dir=r'E:\NeuroMaskAI\hf_cache\instantid', local_dir_use_symlinks=False); print('All models cached.')"

echo.
echo [INFO] Downloading GFPGANv1.4.pth model (Face Enhancement)...
if not exist "GFPGANv1.4.pth" (
    powershell -Command "Invoke-WebRequest -Uri 'https://github.com/TencentARC/GFPGAN/releases/download/v1.3.0/GFPGANv1.4.pth' -OutFile 'GFPGANv1.4.pth'"
    echo [INFO] GFPGAN model downloaded successfully.
) else (
    echo [INFO] GFPGANv1.4.pth already exists. Skipping download.
)

echo.
echo [INFO] Downloading RealESRGAN_x2plus.pth model (Background Upscaling)...
if not exist "RealESRGAN_x2plus.pth" (
    powershell -Command "Invoke-WebRequest -Uri 'https://github.com/xinntao/Real-ESRGAN/releases/download/v0.2.1/RealESRGAN_x2plus.pth' -OutFile 'RealESRGAN_x2plus.pth'"
    echo [INFO] RealESRGAN model downloaded successfully.
) else (
    echo [INFO] RealESRGAN_x2plus.pth already exists. Skipping download.
)

echo.
echo.
echo [INFO] Rerouting massive 8GB downloads to D:\ drive to save C:\ Space...
set HF_HOME=D:\NeuroMaskAI\hf_cache
set INSIGHTFACE_HOME=D:\NeuroMaskAI\insightface

echo [INFO] PRE-CACHING HUGGINGFACE GENERATIVE MODELS (This requires downloading ~8GB)...
echo [INFO] This will take a very long time depending on your internet connection. Do NOT close this window.
python -c "import os; os.environ['HF_HOME']=r'D:\NeuroMaskAI\hf_cache'; from huggingface_hub import hf_hub_download, snapshot_download; print('[+] Downloading SG161222/Realistic_Vision_V5.1_noVAE...'); snapshot_download(repo_id='SG161222/Realistic_Vision_V5.1_noVAE'); print('[+] Downloading ControlNet Inpaint Tensors...'); snapshot_download(repo_id='lllyasviel/control_v11p_sd15_inpaint'); print('[+] Downloading IP-Adapter-FaceID adapter logic...'); hf_hub_download(repo_id='h94/IP-Adapter-FaceID', filename='ip-adapter-faceid_sd15.bin', subfolder='')"

if %errorlevel% equ 0 (
    echo [SUCCESS] All dependencies, ONNX Models, and Diffusion HQ Arrays installed successfully!
    echo Make sure you also have the "inswapper_128.onnx" model in this folder.
) else (
    echo [ERROR] An error occurred during installation.
    echo Ensure you have Microsoft Visual C++ Build Tools installed,
    echo as some packages like insightface may need to be compiled.
)
echo.
pause
