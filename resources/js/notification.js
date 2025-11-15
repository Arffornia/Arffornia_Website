let notificationContainer = null;

/**
 * Creates the notification container div and appends it to the body if it doesn't exist.
 */
function createContainer() {
    // Check if the container is already on the page
    if (document.getElementById('notification-container')) {
        notificationContainer = document.getElementById('notification-container');
        return;
    }

    // Create and append the container
    const container = document.createElement('div');
    container.id = 'notification-container';
    document.body.appendChild(container);
    notificationContainer = container;
}

/**
 * A map of notification types to their corresponding icon image paths.
 */
const imageMap = {
    "update": "/images/icons/update/update_white.png",
    "error": "/images/icons/error/circle.svg",
    "msg": "/images/icons/mail/email_icon.png",
    "success": "/images/icons/mail/email_icon.png",
};

/**
 * Displays a dynamic notification on the screen.
 * @param {string} message The message to display.
 * @param {string} type The type of notification ('msg', 'update', 'error', 'success').
 * @param {number} duration Time in milliseconds before the notification auto-closes. 7000ms = 7 seconds.
 */
export function showNotification(message, type = 'msg', duration = 7000) {
    // Ensure the main container exists
    createContainer();

    const notificationElement = document.createElement('div');
    notificationElement.className = 'notification-content';

    // Get icon URL, fallback to 'msg' icon if type is unknown
    const imageUrl = imageMap[ type ] || imageMap[ 'msg' ];

    // Get current time for the timestamp
    const now = new Date();
    const timeString = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;

    // Populate the notification's HTML structure
    notificationElement.innerHTML = `
        <a class="close-btn">&times;</a>
        <img class="image" src="${imageUrl}" alt="Notification Icon"/>
        <p class="title">${message}</p>
        <p class="time">${timeString}</p>
    `;

    // Add the new notification to the top of the container
    notificationContainer.prepend(notificationElement);

    // Function to gracefully remove the notification
    const removeNotification = () => {
        notificationElement.style.animation = 'slideOut 0.5s ease-out forwards';
        // Remove the element from the DOM after the animation completes
        notificationElement.addEventListener('animationend', () => {
            notificationElement.remove();
        });
    };

    // Add click event to the close button
    notificationElement.querySelector('.close-btn').addEventListener('click', (e) => {
        e.stopPropagation(); // Prevent other click events
        removeNotification();
    });

    // Automatically remove the notification after the specified duration
    setTimeout(removeNotification, duration);
}
