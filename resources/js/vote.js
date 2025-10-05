import { showFlashMessage } from './flash-message.js';

document.addEventListener('DOMContentLoaded', () => {
    document.body.addEventListener('click', async (event) => {
        const verifyButton = event.target.closest('.verify-btn');
        if (!verifyButton) {
            return;
        }

        const siteKey = verifyButton.dataset.siteKey;
        if (!siteKey) {
            console.error('Verify button is missing data-site-key attribute.');
            return;
        }

        const originalIcon = verifyButton.innerHTML;
        verifyButton.innerHTML = `<svg viewBox="0 0 24 24" class="spinner"><path d="M12,4V2A10,10 0 0,0 2,12H4A8,8 0 0,1 12,4Z"/></svg>`; // Spinner SVG
        verifyButton.disabled = true;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const response = await fetch('/vote/verify', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ site: siteKey }),
            });

            const result = await response.json();

            if (response.ok) {
                showFlashMessage(result.message, true);
            } else {
                const errorMessage = result.message || result.error || 'An unknown error occurred.';
                showFlashMessage(errorMessage, false);
            }
        } catch (error) {
            console.error('Error verifying vote:', error);
            showFlashMessage('A network error occurred. Please try again.', false);
        } finally {
            verifyButton.innerHTML = originalIcon;
            verifyButton.disabled = false;
        }
    });

    const style = document.createElement('style');
    style.innerHTML = `
        .spinner {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);
});
