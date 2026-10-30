// Catalog: offers are loaded from the server with search, filter, sorting and pagination

const userProducts = document.querySelector('#user-products');
const searchInput = document.querySelector('#user-product-search');
const categoryFilter = document.querySelector('#user-category-filter');
const sortSelect = document.querySelector('#user-sort');
const noResults = document.querySelector('#user-no-results');
const catalogStatus = document.querySelector('#catalog-status');
const pagination = document.querySelector('#user-pagination');

// Icons for offers without a photo, the same as on the offer page
const categoryIcons = {
    Beauty: 'spa',
    Food: 'restaurant',
    Entertainment: 'movie',
    Travel: 'flight',
    Fitness: 'fitness_center'
};

let currentPage = 1;
let searchTimer = null;
let lastRequestId = 0;

async function loadCategories() {
    try {
        const result = await requestJson('api/categories.php');

        if (!result.success) {
            return;
        }

        result.categories.forEach(category => {
            const option = document.createElement('option');
            option.value = category.id;
            option.textContent = `${category.name} (${category.productCount})`;
            categoryFilter.appendChild(option);
        });
    } catch {
        // The catalog still works with "All Categories" only
    }
}

async function loadProducts() {
    // Only the answer to the newest request is shown
    const requestId = ++lastRequestId;

    const params = new URLSearchParams({
        search: searchInput.value.trim(),
        category: categoryFilter.value,
        sort: sortSelect.value,
        page: currentPage
    });

    catalogStatus.textContent = 'Loading offers...';
    catalogStatus.classList.remove('error');
    catalogStatus.hidden = false;
    noResults.hidden = true;
    userProducts.classList.add('loading');

    try {
        const result = await requestJson(`api/products.php?${params}`);

        if (requestId !== lastRequestId) {
            return;
        }

        if (!result.success) {
            throw new Error(result.error);
        }

        currentPage = result.page;
        catalogStatus.hidden = true;
        noResults.hidden = result.products.length > 0;

        renderProducts(result.products);
        renderPagination(result.page, result.totalPages);
    } catch {
        if (requestId !== lastRequestId) {
            return;
        }

        catalogStatus.textContent = 'Could not load offers. Please try again later.';
        catalogStatus.classList.add('error');
        userProducts.innerHTML = '';
        pagination.innerHTML = '';
    } finally {
        if (requestId === lastRequestId) {
            userProducts.classList.remove('loading');
        }
    }
}

function renderProducts(products) {
    userProducts.innerHTML = '';

    products.forEach(product => {
        const card = document.createElement('article');
        card.className = 'user-product-card';
        card.style.cursor = 'pointer';
        card.tabIndex = 0;

        const image = document.createElement('div');
        image.className = `user-product-image user-product-image-placeholder category-${product.category.toLowerCase()}`;

        if (product.image) {
            const img = document.createElement('img');
            img.className = 'user-product-image';
            img.src = product.image;
            img.alt = product.name;
            image.appendChild(img);
        } else {
            const icon = document.createElement('span');
            icon.className = 'material-symbols-outlined';
            icon.textContent = categoryIcons[product.category] || 'local_offer';
            image.appendChild(icon);
        }

        if (product.oldPrice) {
            const discount = document.createElement('span');
            discount.className = 'card-discount';
            discount.textContent = `-${Math.round((1 - product.price / product.oldPrice) * 100)}%`;
            image.appendChild(discount);
        }

        const info = document.createElement('div');
        info.className = 'user-product-info';

        const name = document.createElement('h3');
        name.textContent = product.name;

        const description = document.createElement('p');
        description.textContent = product.description || product.category;

        const price = document.createElement('span');
        price.className = 'user-product-price';
        price.textContent = formatPrice(product.price);

        if (product.oldPrice) {
            const oldPrice = document.createElement('span');
            oldPrice.className = 'user-product-old-price';
            oldPrice.textContent = formatPrice(product.oldPrice);
            price.appendChild(oldPrice);
        }

        const bought = document.createElement('span');
        bought.className = 'user-product-bought';
        bought.textContent = `${product.sold} bought`;

        info.append(name, description, price, bought);

        if (product.reviewCount > 0) {
            const rating = document.createElement('span');
            rating.className = 'user-product-rating';
            rating.textContent = `★ ${product.rating} (${product.reviewCount})`;
            info.appendChild(rating);
        }

        const status = document.createElement('span');
        status.className = 'user-product-status';
        // An available offer with no coupons left is shown as sold out
        const soldOut = product.status === 'Available' && product.stock === 0;
        status.textContent = soldOut ? 'Sold out' : product.status;

        if (product.status !== 'Available' || soldOut) {
            status.classList.add('unavailable');
        }

        info.appendChild(status);
        card.append(image, info);

        const openProduct = () => {
            window.location.href = `offer.php?id=${product.id}`;
        };

        card.addEventListener('click', openProduct);
        card.addEventListener('keydown', event => {
            if (event.key === 'Enter') {
                openProduct();
            }
        });

        userProducts.appendChild(card);
    });
}

function renderPagination(page, totalPages) {
    pagination.innerHTML = '';

    if (totalPages <= 1) {
        return;
    }

    const addButton = (label, targetPage, disabled = false, active = false) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = label;
        button.disabled = disabled;

        if (active) {
            button.classList.add('active');
            button.setAttribute('aria-current', 'page');
        }

        button.addEventListener('click', () => {
            currentPage = targetPage;
            loadProducts();
            document.querySelector('#offers').scrollIntoView({ behavior: 'smooth' });
        });

        pagination.appendChild(button);
    };

    addButton('‹ Prev', page - 1, page === 1);

    for (let i = 1; i <= totalPages; i++) {
        addButton(String(i), i, false, i === page);
    }

    addButton('Next ›', page + 1, page === totalPages);
}

// Search waits 300 ms after the last key press, so we don't send a request for every letter
searchInput.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        currentPage = 1;
        loadProducts();
    }, 300);
});

categoryFilter.addEventListener('change', () => {
    currentPage = 1;
    loadProducts();
});

sortSelect.addEventListener('change', () => {
    currentPage = 1;
    loadProducts();
});

// A click on a category card selects it in the filter and shows its offers
document.querySelectorAll('.category-card').forEach(card => {
    card.addEventListener('click', () => {
        categoryFilter.value = card.dataset.categoryId;
        currentPage = 1;
        loadProducts();
        document.querySelector('#offers').scrollIntoView({ behavior: 'smooth' });
    });
});

loadCategories();
loadProducts();
