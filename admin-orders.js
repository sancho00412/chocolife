// Admin orders: filters, status change and order details

const ordersBody = document.querySelector('#orders-body');
const ordersSummary = document.querySelector('#orders-summary');
const filterForm = document.querySelector('#orders-filter');
const searchInput = document.querySelector('#orders-search');

const filters = {
    search: searchInput,
    status: document.querySelector('#orders-status'),
    from: document.querySelector('#orders-from'),
    to: document.querySelector('#orders-to'),
    sort: document.querySelector('#orders-sort')
};

let searchTimer = null;
let lastRequestId = 0;

async function loadOrders() {
    const requestId = ++lastRequestId;
    const params = new URLSearchParams();

    Object.entries(filters).forEach(([name, input]) => {
        params.set(name, input.value.trim());
    });

    ordersSummary.textContent = 'Loading orders...';
    ordersSummary.classList.remove('error');

    try {
        const result = await requestJson(`api/admin-orders.php?${params}`);

        if (requestId !== lastRequestId) {
            return;
        }

        if (!result.success) {
            throw new Error(result.error);
        }

        ordersSummary.textContent = result.count > 0
            ? `${result.count} order(s) for ${formatPrice(result.sum)}`
            : 'No orders match the filters.';

        renderOrders(result.orders);
    } catch (error) {
        if (requestId !== lastRequestId) {
            return;
        }

        ordersSummary.textContent = error.message || 'Could not load orders.';
        ordersSummary.classList.add('error');
        ordersBody.innerHTML = '';
    }
}

function renderOrders(orders) {
    ordersBody.innerHTML = '';

    orders.forEach(order => {
        const row = document.createElement('tr');

        const customer = textCell(order.customerName);
        const email = document.createElement('small');
        email.className = 'text-muted cell-note';
        email.textContent = order.email;
        customer.appendChild(email);

        const statusCell = document.createElement('td');
        const select = document.createElement('select');
        select.className = 'status-select';
        select.setAttribute('aria-label', `Status of ${order.orderNumber}`);

        ['Pending', 'Completed', 'Declined'].forEach(status => {
            const option = document.createElement('option');
            option.value = status;
            option.textContent = status;
            select.appendChild(option);
        });

        select.value = order.status;
        select.dataset.status = order.status;
        select.addEventListener('change', () => changeStatus(order, select));
        statusCell.appendChild(select);

        const detailsCell = document.createElement('td');
        const detailsButton = document.createElement('button');
        detailsButton.type = 'button';
        detailsButton.className = 'link-button';
        detailsButton.textContent = 'Details';
        detailsButton.setAttribute('aria-expanded', 'false');
        detailsCell.appendChild(detailsButton);

        row.append(
            textCell(order.orderNumber),
            customer,
            textCell(order.date),
            textCell(order.itemCount),
            textCell(formatPrice(order.total)),
            statusCell,
            detailsCell
        );

        // Hidden row with contacts and items, opened by "Details"
        const detailsRow = document.createElement('tr');
        detailsRow.className = 'details-row';
        detailsRow.hidden = true;

        const detailsContent = document.createElement('td');
        detailsContent.colSpan = 7;

        const contacts = document.createElement('p');
        contacts.textContent = `Phone: ${order.phone} · Address: ${order.address}`;

        const items = document.createElement('ul');
        order.items.forEach(item => {
            const line = document.createElement('li');
            line.textContent = `${item.name} — ${item.quantity} × ${formatPrice(item.price)}`;
            items.appendChild(line);
        });

        detailsContent.append(contacts, items);
        detailsRow.appendChild(detailsContent);

        detailsButton.addEventListener('click', () => {
            detailsRow.hidden = !detailsRow.hidden;
            detailsButton.setAttribute('aria-expanded', String(!detailsRow.hidden));
            detailsButton.textContent = detailsRow.hidden ? 'Details' : 'Hide';
        });

        ordersBody.append(row, detailsRow);
    });
}

async function changeStatus(order, select) {
    const oldStatus = select.dataset.status;
    const newStatus = select.value;

    if (!confirm(`Change ${order.orderNumber} from ${oldStatus} to ${newStatus}?`)) {
        select.value = oldStatus;
        return;
    }

    select.disabled = true;

    try {
        const result = await requestJson('api/admin-orders.php', 'PATCH', {
            orderId: order.id,
            status: newStatus
        });

        if (!result.success) {
            alert(result.error);
            select.value = oldStatus;
            return;
        }

        select.dataset.status = newStatus;
        order.status = newStatus;
    } catch {
        alert('Could not connect to the server.');
        select.value = oldStatus;
    } finally {
        select.disabled = false;
    }
}

filterForm.addEventListener('submit', event => event.preventDefault());

searchInput.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadOrders, 300);
});

[filters.status, filters.from, filters.to, filters.sort].forEach(input => {
    input.addEventListener('change', loadOrders);
});

loadOrders();
