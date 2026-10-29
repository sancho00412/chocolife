
const orderIdElement = document.querySelector('#success-order-id');
const orderStatusElement = document.querySelector('#success-order-status');
const orderTotalElement = document.querySelector('#success-order-total');
const orderCountElement = document.querySelector('#success-order-count');
const successMessage = document.querySelector('#success-message');

function formatPrice(price) {
    return Number(price).toLocaleString('en-US') + ' ₸';
}

function loadSuccessfulOrder() {
    let order;

    try {
        order = JSON.parse(localStorage.getItem('chocolifeLastOrder'));
    } catch {
        order = null;
    }

    if (!order || !order.id) {
        orderIdElement.textContent = 'Not available';
        orderStatusElement.textContent = 'Unknown';
        orderTotalElement.textContent = '—';
        orderCountElement.textContent = '—';

        successMessage.textContent =
            'No recent order was found. You can continue shopping.';

        return;
    }

    orderIdElement.textContent = order.id;
    orderStatusElement.textContent = order.status || 'Pending';
    orderTotalElement.textContent = formatPrice(Number(order.total) || 0);

    const itemCount = (Array.isArray(order.items) ? order.items : [])
        .reduce((sum, item) => {
            return sum + Math.max(1, Number(item.quantity) || 1);
        }, 0);

    orderCountElement.textContent = itemCount;
    successMessage.textContent =
        'Your order has been saved. Thank you for choosing Chocolife!';
}

loadSuccessfulOrder();
