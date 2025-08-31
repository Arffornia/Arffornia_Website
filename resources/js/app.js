import './bootstrap';
import { showFlashMessage } from './flash-message.js';

document.addEventListener('DOMContentLoaded', () => {
    const flashMessageData = document.getElementById('flash-message-data');
    if (flashMessageData) {
        const message = flashMessageData.dataset.message;
        const type = flashMessageData.dataset.type;
        showFlashMessage(message, type === 'success');
    }
});
