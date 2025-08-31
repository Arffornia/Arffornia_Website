/**
 * Displays a dynamic flash message at the top of the screen.
 * @param {string} message The message to display.
 * @param {boolean} isSuccess Whether the message is a success or error message.
 */
export function showFlashMessage(message, isSuccess) {
    const existingFlash = document.querySelector('.dynamic-flash-message');
    if (existingFlash) {
        existingFlash.remove();
    }

    const flashDiv = document.createElement('div');
    flashDiv.className = `dynamic-flash-message ${isSuccess ? 'success' : 'error'}`;
    flashDiv.innerHTML = `<p class="text">${message}</p>`;

    document.body.prepend(flashDiv);

    setTimeout(() => {
        flashDiv.classList.add('visible');
    }, 10);

    setTimeout(() => {
        flashDiv.classList.remove('visible');
        flashDiv.addEventListener('transitionend', () => {
            flashDiv.remove();
        });
    }, 5000);
}
