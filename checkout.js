// Checkout: shows the cart from the server and places the order

const checkoutForm = document.querySelector('#checkout-form');
const checkoutItems = document.querySelector('#checkout-items');
const checkoutTotal = document.querySelector('#checkout-total');
const checkoutMessage = document.querySelector('#checkout-message');
const placeOrderButton = document.querySelector('#place-order-button');

let cartIsEmpty = true;

async function renderOrder() {
    try {
        const cart = await requestJson('api/cart.php');

        if (!cart.success) {
            throw new Error(cart.error);
        }

        checkoutItems.innerHTML = '';
        cartIsEmpty = cart.items.length === 0;

        if (cartIsEmpty) {
            checkoutItems.innerHTML =
                '<p class="page-status">Your cart is empty. <a href="user-home.php#offers">Browse offers</a></p>';
        }

        cart.items.forEach(item => {
            const row = document.createElement('div');
            row.className = 'checkout-product';

            const details = document.createElement('div');

            const name = document.createElement('p');
            name.className = 'checkout-product-name';
            name.textContent = item.name;

            const quantity = document.createElement('p');
            quantity.className = 'checkout-product-quantity';
            quantity.textContent = `Quantity: ${item.quantity}`;

            const subtotal = document.createElement('p');
            subtotal.className = 'checkout-product-price';
            subtotal.textContent = formatPrice(item.subtotal);

            details.append(name, quantity);
            row.append(details, subtotal);
            checkoutItems.appendChild(row);
        });

        checkoutTotal.textContent = formatPrice(cart.total);
    } catch {
        checkoutItems.innerHTML = '<p class="page-status error">Could not load your cart.</p>';
    }
}

function showFieldError(field, message) {
    document.querySelector(`#${field}-error`).textContent = message;
}

// Client-side validation, the server checks the same rules again
function validateForm(data) {
    let valid = true;

    if (data.customerName.length < 2) {
        showFieldError('customerName', 'Enter your full name (at least 2 characters).');
        valid = false;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
        showFieldError('email', 'Enter a valid email address.');
        valid = false;
    }
    if (!/^\+?[\d\s()-]{10,20}$/.test(data.phone)) {
        showFieldError('phone', 'Enter a valid phone number.');
        valid = false;
    }
    if (data.address.length < 5) {
        showFieldError('address', 'Address must contain at least 5 characters.');
        valid = false;
    }

    return valid;
}

checkoutForm.addEventListener('submit', async event => {
    event.preventDefault();

    document.querySelectorAll('.field-error').forEach(element => {
        element.textContent = '';
    });
    checkoutMessage.textContent = '';
    checkoutMessage.className = '';

    if (cartIsEmpty) {
        checkoutMessage.textContent = 'Your cart is empty. Add offers before checkout.';
        checkoutMessage.className = 'error';
        return;
    }

    const data = {
        customerName: document.querySelector('#customer-name').value.trim(),
        email: document.querySelector('#customer-email').value.trim().toLowerCase(),
        phone: document.querySelector('#customer-phone').value.trim(),
        address: document.querySelector('#customer-address').value.trim()
    };

    if (!validateForm(data)) {
        return;
    }

    if (!confirm(`Place the order for ${checkoutTotal.textContent}?`)) {
        return;
    }

    placeOrderButton.disabled = true;
    placeOrderButton.textContent = 'Placing order...';

    try {
        const result = await requestJson('api/orders.php', 'POST', data);

        if (!result.success) {
            Object.entries(result.fields || {}).forEach(([field, message]) => {
                showFieldError(field, message);
            });

            checkoutMessage.textContent = result.error;
            checkoutMessage.className = 'error';
            renderOrder();
            return;
        }

        window.location.href = `order-success.php?id=${result.orderId}`;
    } catch {
        checkoutMessage.textContent = 'Could not connect to the server. Please try again.';
        checkoutMessage.className = 'error';
    } finally {
        placeOrderButton.disabled = false;
        placeOrderButton.textContent = 'Place Order';
    }
});

renderOrder();
