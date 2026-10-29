
let product = null;

try {
    product = JSON.parse(localStorage.getItem('selectedProduct'));
} catch {
    product = null;
}

const productDetails = document.querySelector('#product-details');
const isAdmin = localStorage.getItem('chocolifeUserRole') === 'admin';
const backLink = isAdmin
    ? '<a href="product.html">Back to products</a>'
    : '<a href="user-home.php">Back to offers</a>';

if (!product) {
    productDetails.innerHTML = `
        <p>Product not found.</p>
        ${backLink}
    `;
} else {
    productDetails.innerHTML = `
        ${
            product.image
                ? `<img src="${product.image}" alt="" class="product-detail-image">`
                : ''
        }

        <h2 id="detail-name"></h2>
        <p><strong>Product Number:</strong> <span id="detail-number"></span></p>
        <p><strong>Category:</strong> <span id="detail-category"></span></p>
        <p><strong>Price:</strong> <span id="detail-price"></span></p>
        <p><strong>Status:</strong> <span id="detail-status"></span></p>
        <p><strong>Description:</strong> <span id="detail-description"></span></p>

        ${isAdmin ? '' : '<button id="add-to-cart" type="button">Add to Cart</button>'}
        <p id="cart-message"></p>

        ${backLink}
    `;

    document.querySelector('#detail-name').textContent = product.productName || 'Unnamed product';
    document.querySelector('#detail-number').textContent = product.productNumber || 'N/A';
    document.querySelector('#detail-category').textContent = product.category || 'Other';
    document.querySelector('#detail-price').textContent = product.price ?? 'N/A';
    document.querySelector('#detail-status').textContent = product.status || 'Unknown';
    document.querySelector('#detail-description').textContent =
        product.description || 'No description available';

    document.querySelector('#add-to-cart')?.addEventListener('click', () => {
        const message = document.querySelector('#cart-message');

        if (product.status !== 'Available') {
            message.textContent = 'This offer is currently unavailable.';
            return;
        }

        let cart;

        try {
            cart = JSON.parse(localStorage.getItem('chocolifeCart'));
            if (!Array.isArray(cart)) cart = [];
        } catch {
            cart = [];
        }

        const existingItem = cart.find(item =>
            item.productNumber === product.productNumber
        );

        if (existingItem) {
            existingItem.quantity = Math.min(99, (Number(existingItem.quantity) || 1) + 1);
        } else {
            cart.push({
                productNumber: product.productNumber,
                productName: product.productName,
                price: product.price,
                image: product.image || '',
                quantity: 1
            });
        }

        localStorage.setItem('chocolifeCart', JSON.stringify(cart));
        message.innerHTML = 'Product added to cart! <a href="cart.html">Go to cart</a>';
    });
}
