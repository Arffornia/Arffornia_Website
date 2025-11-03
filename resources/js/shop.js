import { showFlashMessage } from "./flash-message";

/**
 * @type {string|null} Caches the Sanctum API token.
 */
let apiToken = null;

const { csrfToken, baseUrl, isAuth, bestSellerItems, initialItemId } = window.AppData;

document.addEventListener('DOMContentLoaded', () => {
    let currentItemId = null;

    const shopItems = document.querySelectorAll('.shop-item');
    const detailsContent = document.getElementById('item-details-content');
    const detailsLoader = document.getElementById('item-details-loader');

    const showDetails = (itemId) => {
        if (currentItemId === itemId) return;
        currentItemId = itemId;

        detailsContent.style.display = 'none';
        detailsLoader.style.display = 'flex';

        fetch(`${baseUrl}/api/shop/item/${itemId}`)
            .then(response => response.json())
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

    const populateDetails = (item) => {
        document.getElementById('details-image').src = item.img_url;
        document.getElementById('details-name').textContent = item.name;
        document.getElementById('details-description').textContent = item.description;

        const priceContainer = document.getElementById('details-price');
        if (item.promo_price && parseInt(item.promo_price) < parseInt(item.price)) {
            priceContainer.innerHTML = `<span class="item-price-sale">${item.price}</span> ${item.promo_price}`;
        } else {
            priceContainer.innerHTML = item.price;
        }

        const buyButton = document.getElementById('buy-button');
        if (buyButton) {
            buyButton.dataset.itemId = item.id;
            buyButton.dataset.paymentType = item.payment_type;
            buyButton.disabled = !isAuth;
            buyButton.title = isAuth ? '' : 'You must be logged in to purchase.';

            if (item.payment_type === 'real_money') {
                buyButton.innerHTML = `Purchase with <img src="${baseUrl}/images/paypal.svg" alt="PayPal" class="paypal-icon">`;
            } else {
                buyButton.innerHTML = `Purchase with <img src="${baseUrl}/images/coins.png" alt="Coins" class="coin-icon">`;
            }
        }
    };

    const loadRandomBestSeller = () => {
        if (bestSellerItems && bestSellerItems.length > 0) {
            const randomIndex = Math.floor(Math.random() * bestSellerItems.length);
            showDetails(bestSellerItems[ randomIndex ]);
        }
    };

    const handleBuy = async (event) => {
        event.preventDefault();
        const buyButton = event.target.closest('button');
        const itemId = buyButton.dataset.itemId;
        const paymentType = buyButton.dataset.paymentType;

        if (paymentType === 'real_money') {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `${baseUrl}/paypal/create`;
            form.innerHTML = `<input type="hidden" name="_token" value="${csrfToken}"><input type="hidden" name="shop_item_id" value="${itemId}">`;
            document.body.appendChild(form);
            form.submit();
        } else {
            const token = await getApiToken();
            if (!token) return;

            const originalContent = buyButton.innerHTML;
            buyButton.textContent = 'Processing...';
            buyButton.disabled = true;

            try {
                const response = await fetch(`${baseUrl}/shop/buy/${itemId}`, {
                    method: 'POST',
                    headers: createApiHeaders(token),
                });
                const result = await response.json();
                showFlashMessage(result.message, result.success);
            } catch {
                showFlashMessage('An unexpected error occurred.', false);
            } finally {
                buyButton.innerHTML = originalContent;
                buyButton.disabled = false;
            }
        }
    };

    shopItems.forEach(item => {
        item.addEventListener('click', () => showDetails(item.dataset.itemId));
    });

    document.getElementById('buy-button')?.addEventListener('click', handleBuy);

    if (initialItemId) {
        showDetails(initialItemId);
    } else {
        loadRandomBestSeller();
    }
});

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
 * @param {string} token The Sanctum API token.
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
