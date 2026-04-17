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
    modelsLoading: false,
    modelsPromise: null,
    stream: null,

    /**
     * Load face-api.js models from CDN utilizing Promise.all for parallelism.
     */
    async loadModels() {
        if (this.modelsLoaded) return true;
        if (this.modelsLoading) return this.modelsPromise;

        this.modelsLoading = true;
        console.log('Loading face-api.js models (optimized)...');
        
        this.modelsPromise = Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(this.MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(this.MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(this.MODEL_URL)
        ]).then(() => {
            this.modelsLoaded = true;
            this.modelsLoading = false;
            console.log('Face models loaded successfully in parallel.');
            return true;
        }).catch(err => {
            console.error('Failed to load face models:', err);
            this.modelsLoading = false;
            return false;
        });

        return this.modelsPromise;
    },

    /**
     * Start the webcam stream.
     */
    async startWebcam(videoElement) {
        try {
            this.stream = await navigator.mediaDevices.getUserMedia({
                video: { width: 320, height: 240, facingMode: 'user', frameRate: { ideal: 30 } }
            });
            videoElement.srcObject = this.stream;
            return new Promise((resolve) => {
                videoElement.onloadedmetadata = () => {
                    videoElement.play().catch(() => {});
                    resolve(true);
                };
            });
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
        const options = new faceapi.TinyFaceDetectorOptions({ inputSize: 160, scoreThreshold: 0.5 });
        const detection = await faceapi
            .detectSingleFace(videoElement, options)
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

    // Pre-load face models in the background for real-time speed
    FaceLogin.loadModels();

    // Face Login on login page
    const startFaceLoginBtn = document.getElementById('startFaceLogin');
    const faceLoginWebcam = document.getElementById('faceLoginWebcam');
    const faceLoginVideo = document.getElementById('faceLoginVideo');
    const faceLoginCanvas = document.getElementById('faceLoginCanvas');
    const cancelFaceLoginBtn = document.getElementById('cancelFaceLogin');
    const faceLoginStatus = document.getElementById('faceLoginStatus');
    
    let loginScanInterval;
    let isAuthenticating = false;

    if (startFaceLoginBtn) {
        startFaceLoginBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            startFaceLoginBtn.disabled = true;
            startFaceLoginBtn.textContent = 'Hardware initializing...';

            const loaded = await FaceLogin.loadModels();
            if (!loaded) {
                startFaceLoginBtn.disabled = false;
                startFaceLoginBtn.textContent = ' Start Face Login';
                alert('Failed to load face detection AI.');
                return;
            }

            const started = await FaceLogin.startWebcam(faceLoginVideo);
            if (!started) {
                startFaceLoginBtn.disabled = false;
                startFaceLoginBtn.textContent = ' Start Face Login';
                return;
            }

            faceLoginWebcam.style.display = 'block';
            startFaceLoginBtn.style.display = 'none';
            faceLoginStatus.textContent = 'Scanning face in real-time...';
            faceLoginStatus.style.color = '#3b82f6';

            startRealTimeScanner();
        });
    }

    function startRealTimeScanner() {
        const options = new faceapi.TinyFaceDetectorOptions({ inputSize: 160, scoreThreshold: 0.6 });

        loginScanInterval = setInterval(async () => {
             // Avoid evaluating new frames if we are actively verifying against backend
            if (isAuthenticating || !FaceLogin.stream) return;

            const detection = await faceapi
                .detectSingleFace(faceLoginVideo, options)
                .withFaceLandmarks()
                .withFaceDescriptor();

            // Optional real-time bounding box feedback on canvas
            if (faceLoginCanvas && !isAuthenticating) {
                const displaySize = { width: faceLoginVideo.videoWidth || 320, height: faceLoginVideo.videoHeight || 240 };
                // Ensure dimensions have positive non-zero values
                if (displaySize.width > 0 && displaySize.height > 0) {
                    faceapi.matchDimensions(faceLoginCanvas, displaySize);
                    const ctx = faceLoginCanvas.getContext('2d');
                    ctx.clearRect(0, 0, faceLoginCanvas.width, faceLoginCanvas.height);
                    if (detection) {
                        const resizedDetections = faceapi.resizeResults(detection, displaySize);
                        faceapi.draw.drawDetections(faceLoginCanvas, resizedDetections);
                    }
                }
            }

            if (!detection) return; // Continuously scan without blocking

            // Found a valid face, attempt fast background authentication
            isAuthenticating = true;
            faceLoginStatus.textContent = 'Match found! Verifying identity securely...';
            faceLoginStatus.style.color = '#3b82f6';

            const descriptor = Array.from(detection.descriptor);
            const result = await FaceLogin.authenticateFace(descriptor);

            if (result.success) {
                clearInterval(loginScanInterval);
                faceLoginStatus.textContent = ' Access Granted! Redirecting...';
                faceLoginStatus.style.color = '#10b981';
                FaceLogin.stopWebcam();
                
                if (faceLoginCanvas) {
                    const displaySize = { width: faceLoginVideo.videoWidth, height: faceLoginVideo.videoHeight };
                    if (displaySize.width > 0) {
                        const resizedDetections = faceapi.resizeResults(detection, displaySize);
                        const box = new faceapi.draw.DrawBox(resizedDetections.detection.box, { label: result.message, boxColor: '#10b981' });
                        box.draw(faceLoginCanvas);
                    }
                }

                setTimeout(() => {
                    window.location.href = result.redirect || '/NeuroMask/public/';
                }, 800);
            } else {
                faceLoginStatus.textContent = `❌ ${result.error} - Scanning again...`;
                faceLoginStatus.style.color = '#ef4444';
                
                if (faceLoginCanvas) {
                    const displaySize = { width: faceLoginVideo.videoWidth, height: faceLoginVideo.videoHeight };
                    if (displaySize.width > 0) {
                        const resizedDetections = faceapi.resizeResults(detection, displaySize);
                        const box = new faceapi.draw.DrawBox(resizedDetections.detection.box, { label: 'Unknown', boxColor: '#ef4444' });
                        box.draw(faceLoginCanvas);
                    }
                }

                setTimeout(() => { 
                    isAuthenticating = false; 
                    faceLoginStatus.textContent = 'Scanning face in real-time...';
                    faceLoginStatus.style.color = '#3b82f6';
                }, 1000); 
            }
        }, 150); // Frame capture & eval every 150ms 
    }

    if (cancelFaceLoginBtn) {
        cancelFaceLoginBtn.addEventListener('click', (e) => {
            e.preventDefault();
            clearInterval(loginScanInterval);
            isAuthenticating = false;
            FaceLogin.stopWebcam();
            faceLoginWebcam.style.display = 'none';
            startFaceLoginBtn.style.display = 'block';
            startFaceLoginBtn.disabled = false;
            startFaceLoginBtn.textContent = ' Start Face Login';
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
                enrollStatus.textContent = ' ' + result.message;
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
