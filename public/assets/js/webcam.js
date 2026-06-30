import { showToast } from "./notifications.js";

export async function initWebcam(videoEl) {
  if (!videoEl) return null;
  try {
    const stream = await navigator.mediaDevices.getUserMedia({
      video: { width: 640, height: 480 },
      audio: false,
    });
    videoEl.srcObject = stream;
    await videoEl.play();
    return stream;
  } catch {
    setDefaultPhoto();
    showToast("Webcam indisponível", "warning");
    return null;
  }
}

export function stopWebcam(stream, videoEl) {
  if (stream) {
    for (const track of stream.getTracks()) {
      track.stop();
    }
  }
  if (videoEl) {
    videoEl.pause();
    videoEl.srcObject = null;
  }
}

function setDefaultPhoto() {
  const preview = document.getElementById("photoPreview");
  if (preview) {
    preview.innerHTML = '<i class="fas fa-user fa-5x text-muted"></i>';
  }
}

export function capturePhoto(videoEl, canvasEl) {
  if (!videoEl || !canvasEl) return null;
  const canvas = canvasEl;
  canvas.width = videoEl.videoWidth || 640;
  canvas.height = videoEl.videoHeight || 480;
  const ctx = canvas.getContext("2d");
  ctx.drawImage(videoEl, 0, 0, canvas.width, canvas.height);
  return canvas.toDataURL("image/jpeg", 0.8);
}
