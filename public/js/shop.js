/**
 * Stores the Sanctum API token to avoid repeated requests.
 * @type {string|null}
 */
let apiToken = null;

const { csrfToken, baseUrl, isAuth } = window.AppData;


document.addEventListener('DOMContentLoaded', () => {
    const shopItems = document.querySelectorAll('.shop-item');
    const detailsPanel = document.getElementById('item-details-panel');
    const detailsContent = document.getElementById('item-details-content');
    const detailsLoader = document.getElementById('item-details-loader');
    const closeBtn = document.getElementById('details-close-btn');

    const showDetails = (itemId) => {
        detailsPanel.classList.add('visible');
        detailsContent.style.display = 'none';
        detailsLoader.style.display = 'block';

        fetch(`/api/shop/item/${itemId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                return new Promise(resolve => {
                    setTimeout(() => resolve(data), 1000 * 0.3);
                });
            })
            .then(item => {
                populateDetails(item);
                detailsLoader.style.display = 'none';
                detailsContent.style.display = 'block';
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
        if (item.promo_price > 0 && item.promo_price < item.real_price) {
            priceContainer.innerHTML = `<span class="item-price-sale">${item.real_price}</span> ${item.promo_price}`;
        } else {
            priceContainer.innerHTML = item.real_price;
        }

        const buyButton = document.getElementById('buy-button');

        if (buyButton) {
            buyButton.dataset.itemId = item.id;

            if (!isAuth) {
                buyButton.disabled = true;
                buyButton.title = 'You must be logged in to purchase this item.';
            } else {
                buyButton.disabled = false;
                buyButton.title = '';
            }
        }
    };

    const hideDetails = () => {
        detailsPanel.classList.remove('visible');
    };

    const handleBuy = async (event) => {
        const token = await getApiToken();
        if (!token) return;

        event.preventDefault();
        const buyButton = event.target;
        const itemId = buyButton.dataset.itemId;

        if (!isAuth) {
            window.location.href = '/login';
            return;
        }

        const originalText = buyButton.textContent;
        buyButton.textContent = 'Processing...';
        buyButton.disabled = true;

        try {
            const response = await fetch(`/shop/buy/${itemId}`, {
                method: 'POST',
                headers: createApiHeaders(token)

            });

            const result = await response.json();

            showFlashMessage(result.message, result.success);

            if (response.ok) {
                hideDetails();
                // Optional: update the user's balance on the page
            }

        } catch (error) {
            console.error('Purchase error:', error);
            showFlashMessage('An unexpected error occurred.', false);
        } finally {
            buyButton.textContent = originalText;
            buyButton.disabled = false;
        }
    };

    const showFlashMessage = (message, isSuccess) => {
        const existingFlash = document.querySelector('.dynamic-flash-message');
        if (existingFlash) {
            existingFlash.remove();
        }

        const flashDiv = document.createElement('div');
        flashDiv.className = `flashMessage dynamic-flash-message ${isSuccess ? 'success' : 'error'}`;

        const p = document.createElement('p');
        p.className = 'text';
        p.textContent = message;

        flashDiv.appendChild(p);
        document.body.prepend(flashDiv);

        setTimeout(() => {
            flashDiv.style.top = '0';
        }, 100);

        setTimeout(() => {
            flashDiv.style.top = '-100px';
            setTimeout(() => flashDiv.remove(), 500);
        }, 5000);
    };


    shopItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const itemId = item.dataset.itemId;
            if (itemId) {
                showDetails(itemId);
            }
        });
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', hideDetails);
    }

    const buyButton = document.getElementById('buy-button');
    if (buyButton) {
        buyButton.addEventListener('click', handleBuy);
    }

    /**
     * Gets the Sanctum API token for authenticated requests. Caches the token.
     * @returns {Promise<string|null>}
     */
    async function getApiToken() {
        if (apiToken) return apiToken;
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
            'X-CSRF-TOKEN': csrfToken
        };
    }


});
