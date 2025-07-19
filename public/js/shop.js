/**
 * Stores the Sanctum API token to avoid repeated requests.
 * @type {string|null}
 */
let apiToken = null;

const { csrfToken, baseUrl, isAuth, bestSellerItems } = window.AppData;

document.addEventListener('DOMContentLoaded', () => {
    let currentItemId = null;

    const shopWrapper = document.querySelector('.shop-wrapper');
    const shopItems = document.querySelectorAll('.shop-item');
    const detailsContent = document.getElementById('item-details-content');
    const detailsLoader = document.getElementById('item-details-loader');

    /**
     * Displays the details of an item in the panel.
     * @param {string} itemId The ID of the item to display.
     */
    const showDetails = (itemId) => {
        currentItemId = itemId;
        detailsContent.style.display = 'none';
        detailsLoader.style.display = 'flex';

        fetch(`${baseUrl}/api/shop/item/${itemId}`)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => new Promise(resolve => setTimeout(() => resolve(data), 300)))
            .then(item => {
                populateDetails(item);
                detailsLoader.style.display = 'none';
                detailsContent.style.display = 'flex';
            })
            .catch(error => {
                console.error('Error fetching item details:', error);
                detailsLoader.innerHTML = '<p class="error-message">Failed to load item details.</p>';
            });
    };

    /**
     * Populates the details panel with item information.
     * @param {object} item The item object.
     */
    const populateDetails = (item) => {
        document.getElementById('details-image').src = item.img_url;
        document.getElementById('details-name').textContent = item.name;
        document.getElementById('details-description').textContent = item.description;

        const priceContainer = document.getElementById('details-price');
        if (item.promo_price > 0 && item.promo_price < item.real_price) {
            priceContainer.innerHTML = `<span class="item-price-sale">${item.real_price}</span> ${item.promo_price}`;
        } else {
            priceContainer.innerHTML = item.real_price;
        }

        const buyButton = document.getElementById('buy-button');
        if (buyButton) {
            buyButton.dataset.itemId = item.id;
            buyButton.disabled = !isAuth;
            buyButton.title = isAuth ? '' : 'You must be logged in to purchase this item.';
        }
    };

    /**
     * Loads a random item from the best sellers list.
     */
    const loadRandomBestSeller = () => {
        if (bestSellerItems && bestSellerItems.length > 0) {
            const randomIndex = Math.floor(Math.random() * bestSellerItems.length);
            const randomItemId = bestSellerItems[ randomIndex ];
            showDetails(randomItemId);
        } else {
            console.warn('No best seller items available to display by default.');
            detailsLoader.style.display = 'none';
            detailsContent.innerHTML = '<p>No items to display.</p>';
            detailsContent.style.display = 'flex';
        }
    };

    /**
     * Handles the purchase of an item.
     * @param {MouseEvent} event
     */
    const handleBuy = async (event) => {
        const token = await getApiToken();
        if (!token) return;

        event.preventDefault();
        const buyButton = event.target;
        const itemId = buyButton.dataset.itemId;

        const originalText = buyButton.textContent;
        buyButton.textContent = 'Processing...';
        buyButton.disabled = true;

        try {
            const response = await fetch(`${baseUrl}/shop/buy/${itemId}`, {
                method: 'POST',
                headers: createApiHeaders(token),
            });
            const result = await response.json();
            showFlashMessage(result.message, result.success);
        } catch (error) {
            console.error('Purchase error:', error);
            showFlashMessage('An unexpected error occurred.', false);
        } finally {
            buyButton.textContent = originalText;
            buyButton.disabled = false;
        }
    };

    shopItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const itemId = item.dataset.itemId;
            if (itemId && itemId !== currentItemId) {
                showDetails(itemId);
            }
        });
    });

    shopWrapper.addEventListener('click', (e) => {
        if (!e.target.closest('.shop-item') && !e.target.closest('#item-details-panel')) {
            loadRandomBestSeller();
        }
    });

    const buyButton = document.getElementById('buy-button');
    if (buyButton) {
        buyButton.addEventListener('click', handleBuy);
    }

    loadRandomBestSeller();
});

/**
 * Displays a dynamic flash message at the top of the screen.
 * @param {string} message The message to display.
 * @param {boolean} isSuccess Whether the message is a success or error message.
 */
function showFlashMessage(message, isSuccess) {
    const existingFlash = document.querySelector('.dynamic-flash-message');
    if (existingFlash) existingFlash.remove();

    const flashDiv = document.createElement('div');
    flashDiv.className = `flashMessage dynamic-flash-message ${isSuccess ? 'success' : 'error'}`;
    flashDiv.innerHTML = `<p class="text">${message}</p>`;
    document.body.prepend(flashDiv);

    setTimeout(() => { flashDiv.style.top = '20px'; }, 100);
    setTimeout(() => {
        flashDiv.style.top = '-100px';
        setTimeout(() => flashDiv.remove(), 500);
    }, 5000);
}

/**
 * Gets the Sanctum API token for authenticated requests. Caches the token.
 * @returns {Promise<string|null>}
 */
async function getApiToken() {
    if (apiToken || !isAuth) return apiToken;
    try {
        const response = await fetch(`${baseUrl}/api/auth/token/session`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        });
        if (!response.ok) throw new Error('Token fetch failed');
        const data = await response.json();
        apiToken = data.token;
        return apiToken;
    } catch (error) {
        console.error("Token Error:", error);
        alert("Authentication Error. Please refresh the page and try again.");
        return null;
    }
}

/**
 * Creates a standard set of headers for API requests.
 * @param {string} token
 * @returns {object}
 */
function createApiHeaders(token) {
    return {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
    };
}
