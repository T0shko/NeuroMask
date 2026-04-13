/**
 * Neuromax – Upload Page JavaScript (Face Swap)
 * 
 * Handles dual image uploads (source face + target photo),
 * drag-and-drop, file preview, webcam capture, and tab switching.
 */

document.addEventListener('DOMContentLoaded', () => {

    // ── Tab Switching (Source / Target) ──
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const tabId = btn.dataset.tab;
            const group = btn.dataset.group;

            // Update button styles in this group
            document.querySelectorAll(`.tab-btn[data-group="${group}"]`).forEach(b => {
                b.className = 'btn btn-secondary btn-sm tab-btn';
            });
            btn.className = 'btn btn-primary btn-sm tab-btn active';

            // Show/hide tab content
            const parent = btn.closest('.card');
            parent.querySelectorAll('.tab-content').forEach(tc => {
                tc.style.display = 'none';
            });
            document.getElementById(tabId).style.display = 'block';
        });
    });

    // ── File Input + Preview (for both source and target) ──
    function setupFileUpload(fileInputId, zoneId, previewContainerId, previewImgId, fileInfoId) {
        const fileInput = document.getElementById(fileInputId);
        const zone = document.getElementById(zoneId);
        const previewContainer = document.getElementById(previewContainerId);
        const previewImg = document.getElementById(previewImgId);
        const fileInfo = document.getElementById(fileInfoId);

        if (!fileInput) return;

        fileInput.addEventListener('change', (e) => {
            handleFileSelect(e.target.files[0], previewContainer, previewImg, fileInfo);
        });

        // Drag & drop
        if (zone) {
            zone.addEventListener('dragover', (e) => {
                e.preventDefault();
                zone.classList.add('drag-over');
            });
            zone.addEventListener('dragleave', () => {
                zone.classList.remove('drag-over');
            });
            zone.addEventListener('drop', (e) => {
                e.preventDefault();
                zone.classList.remove('drag-over');
                if (e.dataTransfer.files.length > 0) {
                    fileInput.files = e.dataTransfer.files;
                    handleFileSelect(e.dataTransfer.files[0], previewContainer, previewImg, fileInfo);
                }
            });
        }
    }

    function handleFileSelect(file, previewContainer, previewImg, fileInfo) {
        if (!file) return;

        const maxSize = 5 * 1024 * 1024;
        const allowedTypes = ['image/jpeg', 'image/png'];

        if (!allowedTypes.includes(file.type)) {
            alert('Only JPG and PNG files are allowed.');
            return;
        }
        if (file.size > maxSize) {
            alert('File is too large. Maximum size is 5MB.');
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            previewImg.src = e.target.result;
            previewContainer.classList.add('show');
            fileInfo.textContent = `${file.name} (${formatSize(file.size)})`;
        };
        reader.readAsDataURL(file);
    }

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    // Set up both upload zones
    setupFileUpload('sourceFileInput', 'sourceUploadZone', 'sourcePreview', 'sourcePreviewImg', 'sourceFileInfo');
    setupFileUpload('targetFileInput', 'targetUploadZone', 'targetPreview', 'targetPreviewImg', 'targetFileInfo');

    // ── Webcam Handling (generic for source and target) ──
    const webcamStreams = {};

    async function startWebcam(target) {
        const video = document.getElementById(target + 'Video');
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { width: 640, height: 480, facingMode: 'user' }
            });
            video.srcObject = stream;
            video.style.display = 'block';
            webcamStreams[target] = stream;
            return true;
        } catch (err) {
            alert('Could not access webcam: ' + err.message);
            return false;
        }
    }

    function stopWebcam(target) {
        if (webcamStreams[target]) {
            webcamStreams[target].getTracks().forEach(t => t.stop());
            delete webcamStreams[target];
        }
    }

    // Start buttons
    document.querySelectorAll('.webcam-start').forEach(btn => {
        btn.addEventListener('click', async () => {
            const target = btn.dataset.target;
            const started = await startWebcam(target);
            if (started) {
                btn.style.display = 'none';
                btn.parentElement.querySelector('.webcam-capture').style.display = 'inline-flex';
            }
        });
    });

    // Capture buttons
    document.querySelectorAll('.webcam-capture').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.target;
            const video = document.getElementById(target + 'Video');
            const canvas = document.getElementById(target + 'Canvas');
            const dataInput = document.getElementById(target + 'WebcamData');

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);

            dataInput.value = canvas.toDataURL('image/jpeg', 0.9);

            video.style.display = 'none';
            canvas.style.display = 'block';
            btn.style.display = 'none';
            btn.parentElement.querySelector('.webcam-retake').style.display = 'inline-flex';

            stopWebcam(target);
        });
    });

    // Retake buttons
    document.querySelectorAll('.webcam-retake').forEach(btn => {
        btn.addEventListener('click', async () => {
            const target = btn.dataset.target;
            const canvas = document.getElementById(target + 'Canvas');
            const video = document.getElementById(target + 'Video');
            const dataInput = document.getElementById(target + 'WebcamData');

            dataInput.value = '';
            canvas.style.display = 'none';
            video.style.display = 'block';
            btn.style.display = 'none';
            btn.parentElement.querySelector('.webcam-capture').style.display = 'inline-flex';

            await startWebcam(target);
        });
    });

    // ── Form Submit with Loading State ──
    const form = document.getElementById('uploadForm');
    const submitBtn = document.getElementById('submitBtn');
    const progressBar = document.getElementById('progressBar');
    const progressFill = document.getElementById('progressFill');
    const statusText = document.getElementById('statusText');

    if (form) {
        form.addEventListener('submit', (e) => {
            // Check that at least one image is provided for each
            const sourceFile = document.getElementById('sourceFileInput');
            const sourceWebcam = document.getElementById('sourceWebcamData');
            const targetFile = document.getElementById('targetFileInput');
            const targetWebcam = document.getElementById('targetWebcamData');

            const hasSource = (sourceFile && sourceFile.files.length > 0) || (sourceWebcam && sourceWebcam.value);
            const hasTarget = (targetFile && targetFile.files.length > 0) || (targetWebcam && targetWebcam.value);

            if (!hasSource) {
                e.preventDefault();
                alert('Please upload a source face image (Step 1).');
                return;
            }
            if (!hasTarget) {
                e.preventDefault();
                alert('Please upload a target photo (Step 2).');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner spinner-sm"></span> Processing Face Swap...';
            progressBar.style.display = 'block';
            statusText.textContent = 'Uploading images and running AI face swap. This may take 10-30 seconds...';

            let progress = 0;
            const interval = setInterval(() => {
                progress += Math.random() * 8;
                if (progress > 90) progress = 90;
                progressFill.style.width = progress + '%';
            }, 800);

            window.addEventListener('beforeunload', () => clearInterval(interval));
        });
    }
});
