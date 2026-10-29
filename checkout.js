const checkoutForm = document.querySelector('#checkout-form');
const checkoutItems = document.querySelector('#checkout-items');
const checkoutTotal = document.querySelector('#checkout-total');
const checkoutMessage = document.querySelector('#checkout-message');

function readCart() {
    try {
        const cart = JSON.parse(localStorage.getItem('chocolifeCart'));
        return Array.isArray(cart) ? cart : [];
    } catch {
        return [];
    }
}

function parsePrice(value) {
    if (typeof value === 'number') {
        return Number.isFinite(value) && value >= 0 ? value : 0;
    }

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

function renderOrder() {
    const cart = readCart();
    checkoutItems.innerHTML = '';

    if (cart.length === 0) {
        checkoutItems.textContent = 'Your cart is empty.';
        checkoutTotal.textContent = '0 ₸';
        return;
    }

    let total = 0;

    cart.forEach(item => {
        const price = parsePrice(item.price);
        const quantity = Math.max(1, Number(item.quantity) || 1);
        const subtotal = price * quantity;

        total += subtotal;

        const row = document.createElement('div');
        row.className = 'checkout-product';

        const details = document.createElement('div');

        const name = document.createElement('p');
        name.className = 'checkout-product-name';
        name.textContent = item.productName || 'Product';

        const qty = document.createElement('p');
        qty.className = 'checkout-product-quantity';
        qty.textContent = `Quantity: ${quantity}`;

        const priceText = document.createElement('p');
        priceText.className = 'checkout-product-price';
        priceText.textContent = formatPrice(subtotal);

        details.append(name, qty);
        row.append(details, priceText);
        checkoutItems.appendChild(row);
    });

    checkoutTotal.textContent = formatPrice(total);
}

function showError(id, message) {
    const element = document.querySelector(`#${id}`);
    if (element) element.textContent = message;
}

if (checkoutForm) {
    checkoutForm.addEventListener('submit', event => {
        event.preventDefault();

        document.querySelectorAll('.field-error').forEach(element => {
            element.textContent = '';
        });

        checkoutMessage.textContent = '';
        checkoutMessage.style.color = '#d93030';

        const cart = readCart();

        if (cart.length === 0) {
            checkoutMessage.textContent =
                'Your cart is empty. Add products before checkout.';
            return;
        }

        const name = document.querySelector('#customer-name').value.trim();
        const email = document.querySelector('#customer-email').value.trim().toLowerCase();
        const phone = document.querySelector('#customer-phone').value.trim();
        const address = document.querySelector('#customer-address').value.trim();

        let hasError = false;

        if (name.length < 2) {
            showError('name-error', 'Enter your full name (at least 2 characters).');
            hasError = true;
        }

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showError('email-error', 'Enter a valid email address.');
            hasError = true;
        }

        if (!/^\+?[\d\s()-]{10,20}$/.test(phone)) {
            showError('phone-error', 'Enter a valid phone number.');
            hasError = true;
        }

        if (address.length < 5) {
            showError('address-error', 'Address must contain at least 5 characters.');
            hasError = true;
        }

        if (hasError) return;

        const total = cart.reduce((sum, item) => {
            const price = parsePrice(item.price);
            const quantity = Math.max(1, Number(item.quantity) || 1);
            return sum + price * quantity;
        }, 0);

        let orders;

        try {
            orders = JSON.parse(localStorage.getItem('chocolifeOrders')) || [];
            if (!Array.isArray(orders)) orders = [];
        } catch {
            orders = [];
        }

        const order = {
            id: 'ORD-' + Date.now(),
            customerName: name,
            email,
            phone,
            address,
            items: cart,
            total,
            status: 'Pending',
            createdAt: new Date().toISOString()
        };

        orders.push(order);

        try {
            localStorage.setItem('chocolifeOrders', JSON.stringify(orders));
            localStorage.setItem('chocolifeLastOrder', JSON.stringify(order));
            localStorage.setItem('chocolifeCart', JSON.stringify([]));
        } catch {
            checkoutMessage.textContent =
                'Could not save your order. Please try again.';
            return;
        }

        window.location.href = 'order-success.html';
    });
}

function prefillCustomer() {
    try {
        const user = JSON.parse(localStorage.getItem('chocolifeCurrentUser'));
        if (!user) return;

        const nameInput = document.querySelector('#customer-name');
        const emailInput = document.querySelector('#customer-email');

        if (!nameInput.value) {
            nameInput.value = `${user.firstName || ''} ${user.lastName || ''}`.trim();
        }

        if (!emailInput.value) {
            emailInput.value = user.email || '';
        }
    } catch {
        // No saved user, leave the form empty.
    }
}

renderOrder();
prefillCustomer();