// Admin offers: list with filters and pagination, delete

const productsBody = document.querySelector('#products-body');
const productsSummary = document.querySelector('#products-summary');
const productsPagination = document.querySelector('#products-pagination');
const productsSearch = document.querySelector('#products-search');

const productFilters = {
    search: productsSearch,
    category: document.querySelector('#products-category'),
    status: document.querySelector('#products-status'),
    sort: document.querySelector('#products-sort')
};

let productsPage = 1;
let productsSearchTimer = null;
let productsRequestId = 0;

async function loadProductsTable() {
    const requestId = ++productsRequestId;
    const params = new URLSearchParams({ page: productsPage });

    Object.entries(productFilters).forEach(([name, input]) => {
        params.set(name, input.value.trim());
    });

    productsSummary.textContent = 'Loading offers...';
    productsSummary.classList.remove('error');

    try {
        const result = await requestJson(`api/products.php?${params}`);

        if (requestId !== productsRequestId) {
            return;
        }

        if (!result.success) {
            throw new Error(result.error);
        }

        productsPage = result.page;
        productsSummary.textContent = result.total > 0
            ? `${result.total} offer(s) found`
            : 'No offers match the filters.';

        renderProductsTable(result.products);
        renderProductsPagination(result.page, result.totalPages);
    } catch (error) {
        if (requestId !== productsRequestId) {
            return;
        }

        productsSummary.textContent = error.message || 'Could not load offers.';
        productsSummary.classList.add('error');
        productsBody.innerHTML = '';
        productsPagination.innerHTML = '';
    }
}

function renderProductsTable(products) {
    productsBody.innerHTML = '';

    products.forEach(product => {
        const row = document.createElement('tr');

        const nameCell = document.createElement('td');
        const link = document.createElement('a');
        link.href = `offer.php?id=${product.id}`;
        link.textContent = product.name;
        nameCell.appendChild(link);

        const stockCell = textCell(product.stock);
        if (product.stock < 5) {
            stockCell.classList.add('danger');
        }

        const statusCell = document.createElement('td');
        statusCell.appendChild(statusBadge(product.status));

        const actions = document.createElement('td');
        actions.className = 'actions-cell';

        const edit = document.createElement('a');
        edit.href = `admin-product-form.php?id=${product.id}`;
        edit.textContent = 'Edit';

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'link-button danger';
        remove.textContent = 'Delete';
        remove.addEventListener('click', () => deleteProduct(product));

        actions.append(edit, remove);

        row.append(
            nameCell,
            textCell(product.productNumber),
            textCell(product.category),
            textCell(formatPrice(product.price)),
            stockCell,
            textCell(product.sold),
            textCell(product.reviewCount > 0 ? `★ ${product.rating} (${product.reviewCount})` : '—'),
            statusCell,
            actions
        );

        productsBody.appendChild(row);
    });
}

function renderProductsPagination(page, totalPages) {
    productsPagination.innerHTML = '';

    if (totalPages <= 1) {
        return;
    }

    for (let i = 1; i <= totalPages; i++) {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = i;

        if (i === page) {
            button.classList.add('active');
            button.setAttribute('aria-current', 'page');
        }

        button.addEventListener('click', () => {
            productsPage = i;
            loadProductsTable();
        });

        productsPagination.appendChild(button);
    }
}

async function deleteProduct(product) {
    const warning = product.sold > 0
        ? `\n\nIt was sold ${product.sold} time(s). Old orders will keep its name and price.`
        : '';

    if (!confirm(`Delete "${product.name}"?${warning}`)) {
        return;
    }

    try {
        const result = await requestJson('api/admin-products.php', 'DELETE', { id: product.id });

        if (!result.success) {
            alert(result.error);
            return;
        }

        loadProductsTable();
    } catch {
        alert('Could not connect to the server.');
    }
}

document.querySelector('#products-filter').addEventListener('submit', event => event.preventDefault());

productsSearch.addEventListener('input', () => {
    clearTimeout(productsSearchTimer);
    productsSearchTimer = setTimeout(() => {
        productsPage = 1;
        loadProductsTable();
    }, 300);
});

[productFilters.category, productFilters.status, productFilters.sort].forEach(input => {
    input.addEventListener('change', () => {
        productsPage = 1;
        loadProductsTable();
    });
});

loadProductsTable();
