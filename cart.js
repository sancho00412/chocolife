// Cart page: the cart is stored in the database (cart_items)

const cartItems = document.querySelector('#cart-items');
const cartCount = document.querySelector('#cart-count');
const cartTotal = document.querySelector('#cart-total');
const cartStatus = document.querySelector('#cart-status');
const checkoutButton = document.querySelector('#checkout-button');

function showCartError(message) {
    cartStatus.hidden = false;
    cartStatus.textContent = message;
    cartStatus.classList.add('error');
}

function renderCart(cart) {
    cartStatus.hidden = true;
    cartStatus.classList.remove('error');

    cartItems.innerHTML = '';
    cartCount.textContent = cart.count;
    cartTotal.textContent = formatPrice(cart.total);
    updateCartBadge(cart.count);

    // Checkout is possible only when the cart is not empty and everything is in stock
    const canCheckout = cart.items.length > 0 && cart.items.every(item => item.available);
    checkoutButton.classList.toggle('disabled', !canCheckout);
    checkoutButton.setAttribute('aria-disabled', String(!canCheckout));

    if (cart.items.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'empty-cart';
        empty.innerHTML = `
            <h2>Your cart is empty</h2>
            <p>Find something you like in our offers.</p>
            <a href="user-home.php#offers">Browse offers</a>
        `;
        cartItems.appendChild(empty);
        return;
    }

    cart.items.forEach(item => {
        const row = document.createElement('article');
        row.className = 'cart-item';

        const image = document.createElement('div');
        image.className = 'cart-item-image cart-item-placeholder';

        if (item.image) {
            const img = document.createElement('img');
            img.src = item.image;
            img.alt = item.name;
            image.appendChild(img);
        } else {
            image.innerHTML = '<span class="material-symbols-outlined">local_offer</span>';
        }

        const info = document.createElement('div');
        info.className = 'cart-item-info';

        const name = document.createElement('a');
        name.href = `offer.php?id=${item.productId}`;
        name.className = 'line-item-name';
        name.textContent = item.name;

        const price = document.createElement('p');
        price.className = 'cart-item-price';
        price.textContent = `${formatPrice(item.price)} × ${item.quantity} = ${formatPrice(item.subtotal)}`;

        const label = document.createElement('label');
        label.textContent = 'Quantity: ';

        const quantity = document.createElement('input');
        quantity.type = 'number';
        quantity.className = 'cart-quantity';
        quantity.min = 1;
        quantity.max = Math.max(1, Math.min(99, item.stock));
        quantity.value = item.quantity;
        quantity.addEventListener('change', () => changeQuantity(item.productId, quantity));
        label.appendChild(quantity);

        info.append(name, price, label);

        if (!item.available) {
            const warning = document.createElement('p');
            warning.className = 'cart-item-warning';
            warning.textContent = item.stock > 0
                ? `Only ${item.stock} left. Please lower the quantity.`
                : 'This offer is no longer available. Please remove it.';
            info.appendChild(warning);
        }

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'remove-item';
        remove.textContent = 'Remove';
        remove.addEventListener('click', () => removeItem(item.productId, item.name));

        row.append(image, info, remove);
        cartItems.appendChild(row);
    });
}

async function sendCartRequest(method, data) {
    try {
        const result = await requestJson('api/cart.php', method, data);

        if (!result.success) {
            alert(result.error);
            loadCart();
            return;
        }

        renderCart(result);
    } catch {
        showCartError('Could not connect to the server. Please try again.');
    }
}

function changeQuantity(productId, input) {
    const quantity = Number(input.value);
    const max = Number(input.max);

    if (!Number.isInteger(quantity) || quantity < 1 || quantity > max) {
        alert(`Quantity must be from 1 to ${max}.`);
        loadCart();
        return;
    }

    sendCartRequest('PATCH', { productId, quantity });
}

function removeItem(productId, name) {
    if (confirm(`Remove "${name}" from the cart?`)) {
        sendCartRequest('DELETE', { productId });
    }
}

checkoutButton.addEventListener('click', event => {
    if (checkoutButton.classList.contains('disabled')) {
        event.preventDefault();
    }
});

async function loadCart() {
    try {
        const result = await requestJson('api/cart.php');

        if (!result.success) {
            throw new Error(result.error);
        }

        renderCart(result);
    } catch {
        showCartError('Could not load your cart. Please try again later.');
    }
}

loadCart();
