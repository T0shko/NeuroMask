# NeuroMask

A web-based AI face swap platform built with PHP and Python. Upload two photos and the engine swaps the face from one onto the other. Two modes: a fast swap that runs in a few seconds, and a high-quality mode that uses a full enhancement stack for sharper, more polished results.

---

## What it does

- **Face Swap** — upload a source face and a target photo, get the swap back in seconds
- **Two quality modes** — Fast (inswapper, ~3-5s) and HQ (inswapper + RealESRGAN + GFPGAN + Poisson blending, ~15-30s)
- **Face Login** — register your face and log in with your webcam instead of a password (face-api.js)
- **Subscription plans** — usage tiers with job history per user
- **Admin panel** — manage users, jobs, and subscriptions

---

## Tech stack

| Layer | What |
|---|---|
| Frontend | PHP templates, vanilla JS, CSS |
| Backend | PHP 8, MVC structure |
| Database | MySQL 8 |
| AI engine | Python 3.12 |
| Face swap | InsightFace `inswapper_128.onnx` |
| Enhancement | GFPGAN v1.4, RealESRGAN x2 |
| Face detection | InsightFace `buffalo_l` |
| Face login | face-api.js (browser-side) |
| Local server | Laragon (Windows) |

---

## Requirements

- **PHP** 8.1+
- **MySQL** 8.0+
- **Python** 3.10–3.12
- **CUDA GPU** recommended for HQ mode (tested on RTX 3070 8GB)
- **Laragon** or any local Apache/MySQL stack (XAMPP works too)

---

## Setup

### 1. Clone the repo

```bash
git clone https://github.com/yourusername/NeuroMask.git
cd NeuroMask
```

### 2. Database

Import the schema into MySQL:

```bash
mysql -u root -p < database/neuromax.sql
```

Or open `database/neuromax.sql` in phpMyAdmin and run it.

### 3. Configure the app

Open `includes/config.php` and set your database credentials if they differ from the Laragon defaults:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'neuromax');
define('DB_USER', 'root');
define('DB_PASS', '');
```

Also check `BASE_URL` matches where you've placed the project:

```php
define('BASE_URL', '/NeuroMask');
```

### 4. Python dependencies

```bash
cd ai
pip install -r requirements.txt
```

If you have a CUDA GPU, install the GPU runtime too:

```bash
pip install onnxruntime-gpu
```

### 5. Download AI model weights

The model files are too large for GitHub. Download them and place them inside the `ai/` folder:

| File | Where to get it |
|---|---|
| `inswapper_128.onnx` | [InsightFace model zoo](https://github.com/deepinsight/insightface/tree/master/model_zoo) |
| `GFPGANv1.4.pth` | [GFPGAN releases](https://github.com/TencentARC/GFPGAN/releases) |
| `RealESRGAN_x2plus.pth` | [Real-ESRGAN releases](https://github.com/xinntao/Real-ESRGAN/releases) |

Your `ai/` folder should look like this after downloading:

```
ai/
├── process.py
├── requirements.txt
├── inswapper_128.onnx        ← download
├── GFPGANv1.4.pth            ← download
└── RealESRGAN_x2plus.pth     ← download
```

### 6. Point your web server at the project

In Laragon, the project is served from `C:\laragon\www\NeuroMask`. Open `http://localhost/NeuroMask/public/` in your browser.

---

## AI engine usage

The Python script can also be called directly for testing:

```bash
# Fast mode (~3-5 seconds)
python ai/process.py source.jpg target.jpg output.jpg --mode fast

# HQ mode (~15-30 seconds)
python ai/process.py source.jpg target.jpg output.jpg --mode hq

# Debug output
python ai/process.py source.jpg target.jpg output.jpg --mode hq --debug
```

---

## Project structure

```
NeuroMask/
├── admin/              Admin panel pages
├── ai/                 Python AI engine + model weights (weights gitignored)
├── app/
│   ├── controllers/    PHP controllers (Auth, Job, Subscription, etc.)
│   ├── models/         Database models
│   └── services/       AI dispatch, file handling, face matching
├── assets/
│   ├── css/
│   ├── js/
│   ├── uploads/        User-uploaded images (gitignored)
│   └── results/        Generated swaps (gitignored)
├── database/           SQL schema + migrations
├── includes/           Config, DB connection, auth helpers
├── public/             Public-facing PHP pages
└── templates/          Shared header/footer templates
```

---

## Notes

- The `assets/uploads/` and `assets/results/` folders are gitignored. They're created empty — PHP writes to them at runtime.
- Default credentials in `config.php` match Laragon's defaults (`root`, no password). Change them for any non-local deployment.
- HQ mode loads several models into VRAM. On an 8 GB GPU it runs fine. On CPU it will be very slow.
- Face login uses `face-api.js` entirely in the browser — no face data is sent to an external server.

---
