const products = loadProducts();
const userProducts = document.querySelector('#user-products');
const searchInput = document.querySelector('#user-product-search');
const categoryFilter = document.querySelector('#user-category-filter');
const noResults = document.querySelector('#user-no-results');

function renderProducts() {
    const searchValue = searchInput.value.trim().toLowerCase();
    const selectedCategory = categoryFilter.value;

    const filteredProducts = products.filter(product => {
        const name = String(product.productName || '').toLowerCase();
        const description = String(product.description || '').toLowerCase();

        const matchesSearch =
            name.includes(searchValue) ||
            description.includes(searchValue);

        const matchesCategory =
            selectedCategory === 'All' ||
            product.category === selectedCategory;

        return matchesSearch && matchesCategory;
    });

    userProducts.innerHTML = '';

    noResults.hidden = filteredProducts.length > 0;

    filteredProducts.forEach(product => {
        const card = document.createElement('article');
        card.className = 'user-product-card';
        card.style.cursor = 'pointer';

        const image = document.createElement('div');

        if (product.image) {
            const img = document.createElement('img');
            img.className = 'user-product-image';
            img.src = product.image;
            img.alt = product.productName || 'Offer';
            image.appendChild(img);
        } else {
            image.className =
                'user-product-image user-product-image-placeholder';

            const icon = document.createElement('span');
            icon.className = 'material-symbols-outlined';
            icon.textContent = 'local_offer';
            image.appendChild(icon);
        }

        const info = document.createElement('div');
        info.className = 'user-product-info';

        const name = document.createElement('h3');
        name.textContent = product.productName || 'Unnamed offer';

        const description = document.createElement('p');
        description.textContent =
            product.description || product.category || 'Discover this offer';

        const price = document.createElement('span');
        price.className = 'user-product-price';
        price.textContent = product.price ?? 'Price unavailable';

        const status = document.createElement('span');
        status.className = 'user-product-status';
        status.textContent = product.status || 'Unknown';

        if (product.status !== 'Available') {
            status.classList.add('unavailable');
        }

        info.append(name, description, price, status);
        card.append(image, info);

        card.addEventListener('click', () => {
            localStorage.setItem('selectedProduct', JSON.stringify(product));
            window.location.href = 'product-details.html';
        });

        userProducts.appendChild(card);
    });
}

searchInput.addEventListener('input', renderProducts);
categoryFilter.addEventListener('change', renderProducts);

renderProducts();

function updateCartBadge() {
    const badge = document.querySelector('#cart-badge');
    if (!badge) return;

    try {
        const cart = JSON.parse(localStorage.getItem('chocolifeCart')) || [];
        const count = cart.reduce(
            (sum, item) => sum + (Number(item.quantity) || 1),
            0
        );
        badge.textContent = count;
    } catch {
        badge.textContent = '0';
    }
}

updateCartBadge();
