// ---------- ADMIN ACCESS ----------
if (localStorage.getItem('chocolifeUserRole') !== 'admin') {
    window.location.href = 'login.html';
}

// ---------- ELEMENTS ----------
const sideMenu = document.querySelector('aside');
const menuBtn = document.querySelector('#menu-btn');
const closeBtn = document.querySelector('#close-btn');

const main = document.querySelector('main');

// ---------- LOCAL STORAGE ----------
function readArrayFromStorage(key) {
    try {
        const data = JSON.parse(localStorage.getItem(key) || '[]');
        return Array.isArray(data) ? data : [];
    } catch (error) {
        console.error(`Could not read ${key}:`, error);
        return [];
    }
}

// ---------- MENU BUTTON ----------
if (menuBtn && sideMenu) {
    menuBtn.addEventListener('click', () => {
        sideMenu.style.display = 'block';
    });
}

if (closeBtn && sideMenu) {
    closeBtn.addEventListener('click', () => {
        sideMenu.style.display = 'none';
    });
}

// ==========================================
// NAVIGATION: DASHBOARD / USERS / ORDERS
// ==========================================

function findSidebarLink(label) {
    const links = document.querySelectorAll('aside .sidebar a');

    return Array.from(links).find(link => {
        const heading = link.querySelector('h3');
        return heading &&
            heading.textContent.trim().toLowerCase() === label.toLowerCase();
    });
}

const dashboardMenu =
    document.querySelector('#dashboard-menu') ||
    findSidebarLink('Dashboard');

const usersMenu =
    document.querySelector('#users-menu') ||
    findSidebarLink('Users') ||
    findSidebarLink('Customers');

const ordersMenu =
    document.querySelector('#orders-menu') ||
    findSidebarLink('Orders');

// Make sure sections are inside <main>.
// If an old Users section is still inside <aside>,
// move it into the main content area.
let usersSection = document.querySelector('#users-section');
let allOrdersSection = document.querySelector('#all-orders-section');

if (main && usersSection && !main.contains(usersSection)) {
    main.appendChild(usersSection);
}

// Create the Users section if it doesn't exist.
if (main && !usersSection) {
    usersSection = document.createElement('section');
    usersSection.id = 'users-section';
    usersSection.style.display = 'none';

    usersSection.innerHTML = `
        <h1>Users</h1>

        <div class="insights">
            <div class="sales">
                <span class="material-symbols-outlined">groups</span>
                <div class="middle">
                    <div class="left">
                        <h3>Total Users</h3>
                        <h1 id="total-users">0</h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="recent-orders">
            <h2>Registered Users</h2>

            <input
                type="text"
                id="users-search"
                placeholder="Search by name or email..."
            >

            <table>
                <thead>
                    <tr>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody id="users-table-body"></tbody>
            </table>
        </div>
    `;

    main.appendChild(usersSection);
}

// Create the Orders section if it doesn't exist.
if (main && !allOrdersSection) {
    allOrdersSection = document.createElement('section');
    allOrdersSection.id = 'all-orders-section';
    allOrdersSection.style.display = 'none';

    allOrdersSection.innerHTML = `
        <h1>Orders</h1>

        <div class="recent-orders">
            <h2>All Orders</h2>

            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Order Number</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody id="all-orders-table-body"></tbody>
            </table>
        </div>
    `;

    main.appendChild(allOrdersSection);
}

// Connect the Dashboard table to its own tbody.
let recentOrdersTableBody = document.querySelector(
    '#recent-orders-table-body'
);

if (main && !recentOrdersTableBody) {
    const dashboardOrdersTable = Array.from(
        main.querySelectorAll('.recent-orders table')
    ).find(table => !table.closest('#users-section') &&
                    !table.closest('#all-orders-section'));

    if (dashboardOrdersTable) {
        recentOrdersTableBody = dashboardOrdersTable.querySelector('tbody');

        if (recentOrdersTableBody) {
            recentOrdersTableBody.id = 'recent-orders-table-body';
        }
    }
}

function showAdminSection(sectionName) {
    if (!main) return;

    Array.from(main.children).forEach(element => {
        if (element.id === 'users-section') {
            element.style.display =
                sectionName === 'users' ? 'block' : 'none';
        } else if (element.id === 'all-orders-section') {
            element.style.display =
                sectionName === 'orders' ? 'block' : 'none';
        } else {
            // The original Dashboard content.
            element.style.display =
                sectionName === 'dashboard' ? '' : 'none';
        }
    });

    if (sectionName === 'users') {
        renderUsers();
    }

    if (sectionName === 'orders' || sectionName === 'dashboard') {
        renderOrdersTables();
    }
}

if (dashboardMenu) {
    dashboardMenu.addEventListener('click', event => {
        event.preventDefault();
        showAdminSection('dashboard');
    });
}

if (usersMenu) {
    usersMenu.addEventListener('click', event => {
        event.preventDefault();
        showAdminSection('users');
    });
}

if (ordersMenu) {
    ordersMenu.addEventListener('click', event => {
        event.preventDefault();
        showAdminSection('orders');
    });
}

const showAllOrdersLink = document.querySelector('#show-all-orders');

if (showAllOrdersLink) {
    showAllOrdersLink.addEventListener('click', event => {
        event.preventDefault();
        showAdminSection('orders');
    });
}

// ==========================================
// USERS
// ==========================================

function renderUsers() {
    const usersTableBody = document.querySelector('#users-table-body');
    const usersSearch = document.querySelector('#users-search');
    const totalUsers = document.querySelector('#total-users');

    if (!usersTableBody) return;

    const users = readArrayFromStorage('chocolifeUsers');

    if (totalUsers) {
        totalUsers.textContent = users.length;
    }

    const query = usersSearch
        ? usersSearch.value.trim().toLowerCase()
        : '';

    const filteredUsers = users.filter(user => {
        const firstName = user.firstName || '';
        const lastName = user.lastName || '';
        const email = user.email || '';

        return `${firstName} ${lastName} ${email}`
            .toLowerCase()
            .includes(query);
    });

    usersTableBody.replaceChildren();

    if (filteredUsers.length === 0) {
        const row = document.createElement('tr');
        const cell = document.createElement('td');

        cell.colSpan = 3;
        cell.textContent = 'No users found';

        row.appendChild(cell);
        usersTableBody.appendChild(row);
        return;
    }

    filteredUsers.forEach(user => {
        const row = document.createElement('tr');

        [user.firstName, user.lastName, user.email].forEach(value => {
            const cell = document.createElement('td');
            cell.textContent = value || '—';
            row.appendChild(cell);
        });

        usersTableBody.appendChild(row);
    });
}

const usersSearchInput = document.querySelector('#users-search');

if (usersSearchInput) {
    usersSearchInput.addEventListener('input', renderUsers);
}

// ==========================================
// ORDERS
// ==========================================

// orders.js must be loaded before this file.
function getOrders() {
    if (typeof Orders !== 'undefined' && Array.isArray(Orders)) {
        return Orders;
    }

    console.error('Orders array is unavailable. Check orders.js.');
    return [];
}

function parseOrderPrice(price) {
    if (typeof price === 'number') return price;

    return Number(
        String(price ?? '0').replace(/[^\d.-]/g, '')
    ) || 0;
}

function renderOrderRow(order, index) {
    const row = document.createElement('tr');

    const customerCell = document.createElement('td');
    customerCell.textContent = order.productName || 'Customer';

    const idCell = document.createElement('td');
    idCell.textContent = order.productNumber || 'N/A';

    const totalCell = document.createElement('td');
    totalCell.textContent = order.paymentStatus || '0 ₸';

    const statusCell = document.createElement('td');
    const statusSelect = document.createElement('select');

    statusSelect.className = 'order-status-select';
    statusSelect.dataset.orderId = order.productNumber;

    ['Pending', 'Completed', 'Declined'].forEach(status => {
        const option = document.createElement('option');
        option.value = status;
        option.textContent = status;
        statusSelect.appendChild(option);
    });

    statusSelect.value = ['Pending', 'Completed', 'Declined']
        .includes(order.shipping)
        ? order.shipping
        : 'Pending';

    statusCell.appendChild(statusSelect);

    const detailsCell = document.createElement('td');
    const detailsButton = document.createElement('button');

    detailsButton.type = 'button';
    detailsButton.className = 'order-details-btn';
    detailsButton.dataset.orderIndex = index;
    detailsButton.textContent = 'Details';

    detailsCell.appendChild(detailsButton);

    row.append(
        customerCell,
        idCell,
        totalCell,
        statusCell,
        detailsCell
    );

    return row;
}

function renderOrdersTables() {
    const orders = getOrders();

    const recentBody = document.querySelector(
        '#recent-orders-table-body'
    );

    const allOrdersBody = document.querySelector(
        '#all-orders-table-body'
    );

    if (recentBody) {
        recentBody.replaceChildren();

        // Show the latest five entries on the Dashboard.
        orders.slice(-5).reverse().forEach(order => {
            const index = orders.indexOf(order);
            recentBody.appendChild(renderOrderRow(order, index));
        });
    }

    if (allOrdersBody) {
        allOrdersBody.replaceChildren();

        orders.forEach((order, index) => {
            allOrdersBody.appendChild(renderOrderRow(order, index));
        });
    }
}

function showOrderDetails(order) {
    const itemsText = (order.items || []).map(item => {
        const price = parseOrderPrice(item.price);
        const quantity = Number(item.quantity) || 1;
        const subtotal = price * quantity;

        return `${item.productName || 'Product'} — ${quantity} шт. × ` +
            `${price.toLocaleString('en-US')} ₸ = ` +
            `${subtotal.toLocaleString('en-US')} ₸`;
    }).join('\n');

    alert(
        `Order: ${order.productNumber}\n` +
        `Customer: ${order.productName}\n` +
        `Email: ${order.customerEmail || 'Not provided'}\n` +
        `Phone: ${order.customerPhone || 'Not provided'}\n` +
        `Address: ${order.customerAddress || 'Not provided'}\n` +
        `Items:\n${itemsText || order.itemNames || 'No products'}\n\n` +
        `Total: ${order.paymentStatus}\n` +
        `Status: ${order.shipping}\n` +
        `Date: ${order.createdAt || 'Not provided'}`
    );
}

// One click handler for both order tables.
document.addEventListener('click', event => {
    const button = event.target.closest('.order-details-btn');
    if (!button) return;

    const index = Number(button.dataset.orderIndex);
    const order = getOrders()[index];

    if (order) {
        showOrderDetails(order);
    }
});

// One status handler for both order tables.
document.addEventListener('change', event => {
    const select = event.target.closest('.order-status-select');
    if (!select) return;

    const orderId = select.dataset.orderId;
    const newStatus = select.value;

    try {
        const savedOrders = readArrayFromStorage('chocolifeOrders');

        const savedOrder = savedOrders.find(
            item => String(item.id) === String(orderId)
        );

        if (!savedOrder) {
            alert('Order not found in saved orders.');
            renderOrdersTables();
            return;
        }

        savedOrder.status = newStatus;

        localStorage.setItem(
            'chocolifeOrders',
            JSON.stringify(savedOrders)
        );

        // Synchronize the in-memory Orders array.
        const order = getOrders().find(
            item => String(item.productNumber) === String(orderId)
        );

        if (order) {
            order.shipping = newStatus;
        }

        // Update both tables.
        renderOrdersTables();
    } catch (error) {
        console.error('Failed to update order status:', error);
        alert('Could not update order status.');
    }
});

// ==========================================
// PRODUCT SEARCH AND CATEGORY FILTER
// ==========================================

const productSearch = document.querySelector('#product-search');
const categoryFilter = document.querySelector('#product-category-filter');
const searchResults = document.querySelector('#search-results');

function getProducts() {
    return readArrayFromStorage('products');
}

function closeSearchResults() {
    if (searchResults) {
        searchResults.replaceChildren();
        searchResults.style.display = 'none';
    }
}

function renderProductSearch() {
    if (!productSearch || !searchResults) return;

    const searchValue = productSearch.value.trim().toLowerCase();
    const selectedCategory = categoryFilter
        ? categoryFilter.value
        : 'All';

    const products = getProducts();

    const filteredProducts = products.filter(product => {
        const name = String(product.productName || '').toLowerCase();

        const matchesSearch = name.includes(searchValue);

        const matchesCategory =
            selectedCategory === 'All' ||
            product.category === selectedCategory;

        return matchesSearch && matchesCategory;
    });

    searchResults.replaceChildren();

    if (!searchValue && selectedCategory === 'All') {
        searchResults.style.display = 'none';
        return;
    }

    if (filteredProducts.length === 0) {
        const emptyMessage = document.createElement('div');
        emptyMessage.textContent = 'No products found';
        searchResults.appendChild(emptyMessage);
        searchResults.style.display = 'block';
        return;
    }

    filteredProducts.forEach(product => {
        const item = document.createElement('div');
        item.className = 'search-result-item';
        item.style.cursor = 'pointer';

        const name = document.createElement('strong');
        name.textContent = product.productName || 'Product';

        const price = document.createElement('span');
        price.textContent = product.price || '';

        item.append(name, price);

        item.addEventListener('click', () => {
            localStorage.setItem(
                'selectedProduct',
                JSON.stringify(product)
            );

            window.location.href = 'product-details.html';
        });

        searchResults.appendChild(item);
    });

    searchResults.style.display = 'block';
}

if (productSearch) {
    productSearch.addEventListener('input', renderProductSearch);
}

if (categoryFilter) {
    categoryFilter.addEventListener('change', renderProductSearch);
}

document.addEventListener('click', event => {
    const searchContainer = document.querySelector('.search');

    if (searchContainer && !searchContainer.contains(event.target)) {
        closeSearchResults();
    }
});

// ==========================================
// ADMIN LOGOUT
// ==========================================

const adminLogout = document.querySelector('#admin-logout');

if (adminLogout) {
    adminLogout.addEventListener('click', event => {
        event.preventDefault();

        localStorage.removeItem('chocolifeUserRole');
        localStorage.removeItem('chocolifeCurrentUser');

        window.location.href = 'welcome.html';
    });
}

// ==========================================
// DELETE PRODUCT HELPER
// ==========================================

function deleteProduct(productNumber) {
    const products = getProducts();

    const updatedProducts = products.filter(
        product =>
            String(product.productNumber) !== String(productNumber)
    );

    localStorage.setItem(
        'products',
        JSON.stringify(updatedProducts)
    );

    window.location.reload();
}

// ==========================================
// INITIAL RENDER
// ==========================================

renderOrdersTables();
renderUsers();