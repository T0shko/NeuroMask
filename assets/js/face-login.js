/**
 * Neuromax – Face Login JavaScript
 * 
 * Handles face detection using face-api.js, webcam capture,
 * face enrollment, and face-based authentication.
 * 
 * Uses the vladmandic fork: @vladmandic/face-api
 * Models are loaded from CDN for simplicity.
 */

const FaceLogin = {
    // CDN URLs for face-api.js model weights
    MODEL_URL: 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/',
    
    // API endpoints
    ENROLL_URL: '/NeuroMask/app/controllers/FaceLoginController.php?action=enroll',
    AUTH_URL: '/NeuroMask/app/controllers/FaceLoginController.php?action=authenticate',
    REMOVE_URL: '/NeuroMask/app/controllers/FaceLoginController.php?action=remove',

    modelsLoaded: false,
    stream: null,

    /**
     * Load face-api.js models from CDN.
     */
    async loadModels() {
        if (this.modelsLoaded) return true;

        try {
            console.log('Loading face-api.js models...');
            await faceapi.nets.tinyFaceDetector.loadFromUri(this.MODEL_URL);
            await faceapi.nets.faceLandmark68Net.loadFromUri(this.MODEL_URL);
            await faceapi.nets.faceRecognitionNet.loadFromUri(this.MODEL_URL);
            this.modelsLoaded = true;
            console.log('Face models loaded successfully.');
            return true;
        } catch (err) {
            console.error('Failed to load face models:', err);
            return false;
        }
    },

    /**
     * Start the webcam stream.
     */
    async startWebcam(videoElement) {
        try {
            this.stream = await navigator.mediaDevices.getUserMedia({
                video: { width: 320, height: 240, facingMode: 'user' }
            });
            videoElement.srcObject = this.stream;
            return true;
        } catch (err) {
            console.error('Webcam error:', err);
            alert('Could not access webcam: ' + err.message);
            return false;
        }
    },

    /**
     * Stop the webcam stream.
     */
    stopWebcam() {
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }
    },

    /**
     * Detect face and extract 128-float descriptor from a video element.
     */
    async detectFace(videoElement) {
        const detection = await faceapi
            .detectSingleFace(videoElement, new faceapi.TinyFaceDetectorOptions())
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (!detection) {
            return null;
        }

        // Return the 128-float descriptor as regular array
        return Array.from(detection.descriptor);
    },

    /**
     * Enroll face descriptor for the logged-in user.
     */
    async enrollFace(descriptor) {
        try {
            const response = await fetch(this.ENROLL_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ descriptor })
            });
            return await response.json();
        } catch (err) {
            return { success: false, error: err.message };
        }
    },

    /**
     * Authenticate using a face descriptor.
     */
    async authenticateFace(descriptor) {
        try {
            const response = await fetch(this.AUTH_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ descriptor })
            });
            return await response.json();
        } catch (err) {
            return { success: false, error: err.message };
        }
    },

    /**
     * Remove face data for logged-in user.
     */
    async removeFace() {
        try {
            const response = await fetch(this.REMOVE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            return await response.json();
        } catch (err) {
            return { success: false, error: err.message };
        }
    }
};


// ── Login Page: Face Login Flow ──
document.addEventListener('DOMContentLoaded', () => {

    // Face Login on login page
    const startFaceLoginBtn = document.getElementById('startFaceLogin');
    const faceLoginWebcam = document.getElementById('faceLoginWebcam');
    const faceLoginVideo = document.getElementById('faceLoginVideo');
    const captureFaceLoginBtn = document.getElementById('captureFaceLogin');
    const cancelFaceLoginBtn = document.getElementById('cancelFaceLogin');
    const faceLoginStatus = document.getElementById('faceLoginStatus');

    if (startFaceLoginBtn) {
        startFaceLoginBtn.addEventListener('click', async () => {
            startFaceLoginBtn.disabled = true;
            startFaceLoginBtn.textContent = 'Loading models...';

            const loaded = await FaceLogin.loadModels();
            if (!loaded) {
                startFaceLoginBtn.disabled = false;
                startFaceLoginBtn.textContent = '📷 Start Face Login';
                alert('Failed to load face detection models.');
                return;
            }

            const started = await FaceLogin.startWebcam(faceLoginVideo);
            if (!started) {
                startFaceLoginBtn.disabled = false;
                startFaceLoginBtn.textContent = '📷 Start Face Login';
                return;
            }

            faceLoginWebcam.style.display = 'block';
            startFaceLoginBtn.style.display = 'none';
        });
    }

    if (captureFaceLoginBtn) {
        captureFaceLoginBtn.addEventListener('click', async () => {
            faceLoginStatus.textContent = 'Detecting face...';
            captureFaceLoginBtn.disabled = true;

            const descriptor = await FaceLogin.detectFace(faceLoginVideo);

            if (!descriptor) {
                faceLoginStatus.textContent = '❌ No face detected. Please position your face clearly.';
                captureFaceLoginBtn.disabled = false;
                return;
            }

            faceLoginStatus.textContent = 'Authenticating...';

            const result = await FaceLogin.authenticateFace(descriptor);

            if (result.success) {
                faceLoginStatus.textContent = '✅ ' + result.message;
                faceLoginStatus.style.color = '#10b981';
                FaceLogin.stopWebcam();
                // Redirect to dashboard
                setTimeout(() => {
                    window.location.href = result.redirect;
                }, 1000);
            } else {
                faceLoginStatus.textContent = '❌ ' + result.error;
                faceLoginStatus.style.color = '#ef4444';
                captureFaceLoginBtn.disabled = false;
            }
        });
    }

    if (cancelFaceLoginBtn) {
        cancelFaceLoginBtn.addEventListener('click', () => {
            FaceLogin.stopWebcam();
            faceLoginWebcam.style.display = 'none';
            startFaceLoginBtn.style.display = 'block';
            startFaceLoginBtn.disabled = false;
            startFaceLoginBtn.textContent = '📷 Start Face Login';
        });
    }

    // ── Profile Page: Face Enrollment ──
    const enrollBtn = document.getElementById('enrollFace');
    const reEnrollBtn = document.getElementById('reEnrollFace');
    const removeBtn = document.getElementById('removeFace');
    const enrollWebcam = document.getElementById('enrollWebcam');
    const enrollVideo = document.getElementById('enrollVideo');
    const captureEnrollBtn = document.getElementById('captureEnroll');
    const cancelEnrollBtn = document.getElementById('cancelEnroll');
    const enrollStatus = document.getElementById('enrollStatus');

    async function startEnrollment() {
        if (!enrollWebcam) return;

        enrollWebcam.style.display = 'block';
        enrollStatus.textContent = 'Loading models...';

        const loaded = await FaceLogin.loadModels();
        if (!loaded) {
            enrollStatus.textContent = 'Failed to load models.';
            return;
        }

        const started = await FaceLogin.startWebcam(enrollVideo);
        if (!started) {
            enrollWebcam.style.display = 'none';
            return;
        }

        enrollStatus.textContent = 'Position your face and click Capture.';
    }

    if (enrollBtn) {
        enrollBtn.addEventListener('click', startEnrollment);
    }

    if (reEnrollBtn) {
        reEnrollBtn.addEventListener('click', startEnrollment);
    }

    if (captureEnrollBtn) {
        captureEnrollBtn.addEventListener('click', async () => {
            enrollStatus.textContent = 'Detecting face...';
            captureEnrollBtn.disabled = true;

            const descriptor = await FaceLogin.detectFace(enrollVideo);

            if (!descriptor) {
                enrollStatus.textContent = '❌ No face detected. Try again.';
                captureEnrollBtn.disabled = false;
                return;
            }

            enrollStatus.textContent = 'Enrolling...';
            const result = await FaceLogin.enrollFace(descriptor);

            if (result.success) {
                enrollStatus.textContent = '✅ ' + result.message;
                enrollStatus.style.color = '#10b981';
                FaceLogin.stopWebcam();
                setTimeout(() => location.reload(), 1500);
            } else {
                enrollStatus.textContent = '❌ ' + result.error;
                enrollStatus.style.color = '#ef4444';
                captureEnrollBtn.disabled = false;
            }
        });
    }

    if (cancelEnrollBtn) {
        cancelEnrollBtn.addEventListener('click', () => {
            FaceLogin.stopWebcam();
            enrollWebcam.style.display = 'none';
        });
    }

    if (removeBtn) {
        removeBtn.addEventListener('click', async () => {
            if (!confirm('Remove your face login data?')) return;
            const result = await FaceLogin.removeFace();
            if (result.success) {
                location.reload();
            } else {
                alert(result.error);
            }
        });
    }
});
