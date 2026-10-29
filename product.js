if (localStorage.getItem('chocolifeUserRole') !== 'admin') {
    window.location.href = 'login.html';
}

let products = loadProducts();

const tableBody = document.querySelector('table tbody');

if (tableBody) {
    renderProducts();
}

function renderProducts() {
    if (!tableBody) return;

    tableBody.innerHTML = '';

    products.forEach(product => {
        const tr = document.createElement('tr');

        tr.innerHTML = `
            <td>${product.productName}</td>
            <td>${product.productNumber}</td>
            <td>${product.price}</td>
            <td class="${product.status === 'Unavailable' ? 'danger' : 'success'}">${product.status || 'Available'}</td>
            <td>
                <button type="button" class="primary details-product">
                    Details
                </button>
                <button type="button" class="primary edit-product">
                    Edit
                </button>
                <button type="button" class="danger delete-product">
                    Delete
                </button>
            </td>
        `;

        tableBody.appendChild(tr);
    });
}

function saveProducts() {
    localStorage.setItem('products', JSON.stringify(products));
}

// Handle Details, Edit and Delete
if (tableBody) {
    tableBody.addEventListener('click', event => {
        const button = event.target.closest(
            '.delete-product, .edit-product, .details-product'
        );

        if (!button) return;

        const row = button.closest('tr');
        const productNumber = row.children[1].textContent.trim();

        const product = products.find(item =>
            String(item.productNumber) === productNumber
        );

        if (!product) return;

        if (button.classList.contains('delete-product')) {
            const confirmed = confirm(
                `Are you sure you want to delete "${product.productName}"?`
            );

            if (!confirmed) return;

            products = products.filter(item =>
                String(item.productNumber) !== productNumber
            );

            saveProducts();
            renderProducts();
        }

        if (button.classList.contains('edit-product')) {
            localStorage.setItem(
                'editProduct',
                JSON.stringify(product)
            );

            window.location.href = 'add-product.html?edit=1';
        }

        if (button.classList.contains('details-product')) {
            localStorage.setItem(
                'selectedProduct',
                JSON.stringify(product)
            );

            window.location.href = 'product-details.html';
        }
    });
}