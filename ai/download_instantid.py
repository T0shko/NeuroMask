import os
import sys

os.environ["HF_HOME"] = r"E:\NeuroMaskAI\hf_cache"

from huggingface_hub import snapshot_download, hf_hub_download

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
INSTANTID_CACHE = os.path.join(os.environ["HF_HOME"], "instantid")


def dl(label, fn, **kwargs):
    print(f"\n{label}", flush=True)
    try:
        result = fn(**kwargs)
        print(f"  OK -> {result}", flush=True)
        return result
    except Exception as e:
        print(f"  ERROR: {e}", flush=True)
        return None


# [1] Pipeline script -> goes directly into the ai/ folder
dl(
    "[1/4] InstantID pipeline script (pipeline_stable_diffusion_xl_instantid.py)...",
    hf_hub_download,
    repo_id="InstantX/InstantID",
    filename="pipeline_stable_diffusion_xl_instantid.py",
    local_dir=SCRIPT_DIR,
)

# [2] ip-adapter.bin (~300 MB)
dl(
    "[2/4] InstantID ip-adapter.bin (~300 MB)...",
    hf_hub_download,
    repo_id="InstantX/InstantID",
    filename="ip-adapter.bin",
    local_dir=INSTANTID_CACHE,
)

# [3] ControlNet weights (~1.4 GB)
dl(
    "[3/4] InstantID ControlNetModel (~1.4 GB)...",
    snapshot_download,
    repo_id="InstantX/InstantID",
    allow_patterns=["ControlNetModel/*", "ControlNetModel/**"],
)

# [4] SDXL base — YamerMIX_v8 first, fall back to RealVisXL_V4.0
print("\n[4/4] SDXL base model (~6-7 GB) — this is the big download...", flush=True)
downloaded = False
for repo in ["wangqixun/YamerMIX_v8", "SG161222/RealVisXL_V4.0"]:
    try:
        path = snapshot_download(repo_id=repo)
        print(f"  OK -> {path}", flush=True)
        if repo != "wangqixun/YamerMIX_v8":
            print("", flush=True)
            print(f"  NOTE: wangqixun/YamerMIX_v8 was unavailable.", flush=True)
            print(f"  Open process.py and change HQ_BASE_MODEL to: {repo}", flush=True)
        downloaded = True
        break
    except Exception as e:
        print(f"  {repo} failed: {e}", flush=True)

if not downloaded:
    print("\nFAILED: No SDXL base model could be downloaded.", flush=True)
    sys.exit(1)

print("\nAll InstantID downloads complete.", flush=True)
