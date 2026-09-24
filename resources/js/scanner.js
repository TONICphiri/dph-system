import { Html5Qrcode } from 'html5-qrcode';

/**
 * Reads the QR code on a health passport card with the device camera and
 * submits the value to the lookup form.
 */
document.addEventListener('DOMContentLoaded', () => {
    const region = document.getElementById('qr-reader');
    const form = document.getElementById('lookup-form');
    const input = document.getElementById('code');
    const status = document.getElementById('scanner-status');
    const startButton = document.getElementById('start-scan');

    if (!region || !form || !input) return;

    const scanner = new Html5Qrcode('qr-reader');
    let running = false;

    const setStatus = (text) => { if (status) status.textContent = text; };

    startButton?.addEventListener('click', async () => {
        if (running) return;

        try {
            await scanner.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 220, height: 220 } },
                async (decoded) => {
                    await scanner.stop();
                    running = false;
                    input.value = decoded;
                    setStatus('Code read. Opening the patient record.');
                    form.submit();
                },
                () => {},
            );
            running = true;
            startButton.disabled = true;
            setStatus('Hold the passport card in front of the camera.');
        } catch {
            setStatus('The camera could not be started. Allow camera access, or type the passport number below.');
        }
    });
});
