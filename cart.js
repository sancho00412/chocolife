
const cartItems = document.querySelector('#cart-items');
const cartCount = document.querySelector('#cart-count');
const cartTotal = document.querySelector('#cart-total');
const checkoutButton = document.querySelector('#checkout-button');

function getCart() {
    try {
        const cart = JSON.parse(localStorage.getItem('chocolifeCart'));
        return Array.isArray(cart) ? cart : [];
    } catch {
        return [];
    }
}

function saveCart(cart) {
    localStorage.setItem('chocolifeCart', JSON.stringify(cart));
}

function parsePrice(value) {
    if (typeof value === 'number') {
        return value;
    }

    // Supports prices like "100000 ₸" and "100,000 ₸"
    const normalized = String(value ?? '')
        .replace(/\s/g, '')
        .replace(/₸/g, '')
        .replace(/,/g, '');

    const price = Number(normalized);

    return Number.isFinite(price) && price >= 0 ? price : 0;
}

function formatPrice(price) {
    return price.toLocaleString('en-US') + ' ₸';
}

function renderCart() {
    const cart = getCart();

    cartItems.innerHTML = '';

    if (cart.length === 0) {
        cartItems.innerHTML = `
            <div class="empty-cart">
                <h2>Your cart is empty</h2>
                <p>Add some offers to get started.</p>
                <a href="user-home.html">Browse offers</a>
            </div>
        `;

        cartCount.textContent = '0';
        cartTotal.textContent = '0 ₸';
        return;
    }

    let total = 0;
    let count = 0;

    cart.forEach((item, index) => {
        const price = parsePrice(item.price);
        const quantity = Math.max(
            1,
            Math.min(99, Number(item.quantity) || 1)
        );

        total += price * quantity;
        count += quantity;

        const card = document.createElement('article');
        card.className = 'cart-item';

        if (item.image) {
            const image = document.createElement('img');
            image.className = 'cart-item-image';
            image.src = item.image;
            image.alt = item.productName || 'Product';
            card.appendChild(image);
        }

        const info = document.createElement('div');
        info.className = 'cart-item-info';

        const name = document.createElement('h3');
        name.textContent = item.productName || 'Product';

        const priceText = document.createElement('p');
        priceText.className = 'cart-item-price';
        priceText.textContent = formatPrice(price);

        const quantityLabel = document.createElement('label');
        quantityLabel.textContent = 'Quantity: ';

        const quantityInput = document.createElement('input');
        quantityInput.className = 'cart-quantity';
        quantityInput.type = 'number';
        quantityInput.min = '1';
        quantityInput.max = '99';
        quantityInput.value = quantity;

        quantityInput.addEventListener('change', () => {
            const updatedCart = getCart();

            if (!updatedCart[index]) return;

            const newQuantity = Math.max(
                1,
                Math.min(99, Number(quantityInput.value) || 1)
            );

            updatedCart[index].quantity = newQuantity;
            saveCart(updatedCart);
            renderCart();
        });

        quantityLabel.appendChild(quantityInput);
        info.append(name, priceText, quantityLabel);

        const removeButton = document.createElement('button');
        removeButton.className = 'remove-item';
        removeButton.type = 'button';
        removeButton.textContent = 'Remove';

        removeButton.addEventListener('click', () => {
            const updatedCart = getCart();
            updatedCart.splice(index, 1);
            saveCart(updatedCart);
            renderCart();
        });

        card.append(info, removeButton);
        cartItems.appendChild(card);
    });

    cartCount.textContent = String(count);
    cartTotal.textContent = formatPrice(total);
}

if (checkoutButton) {
    checkoutButton.addEventListener('click', () => {
        if (getCart().length === 0) {
            alert('Your cart is empty.');
            return;
        }

        window.location.href = 'checkout.html';
    });
}

renderCart();
