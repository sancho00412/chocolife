const profileForm = document.querySelector('#profile-form');
const profileMessage = document.querySelector('#profile-message');
const profileOrders = document.querySelector('#profile-orders');

function readStoredData(key, fallback) {
    try {
        const value = JSON.parse(localStorage.getItem(key));
        return value ?? fallback;
    } catch {
        return fallback;
    }
}

function formatPrice(price) {
    return (Number(price) || 0).toLocaleString('en-US') + ' ₸';
}

function escapeHTML(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    })[character]);
}

const currentUser = readStoredData('chocolifeCurrentUser', null);

if (!currentUser || !currentUser.email) {
    window.location.href = 'login.html';
} else {
    const firstNameInput = document.querySelector('#profile-first-name');
    const lastNameInput = document.querySelector('#profile-last-name');
    const emailInput = document.querySelector('#profile-email');
    const displayName = document.querySelector('#profile-display-name');
    const displayEmail = document.querySelector('#profile-display-email');

    function renderProfile() {
        const firstName = currentUser.firstName || '';
        const lastName = currentUser.lastName || '';

        firstNameInput.value = firstName;
        lastNameInput.value = lastName;
        emailInput.value = currentUser.email;

        displayName.textContent =
            `${firstName} ${lastName}`.trim() || currentUser.email;

        displayEmail.textContent = currentUser.email;
    }

    function renderOrders() {
        const storedOrders = readStoredData('chocolifeOrders', []);
        const allOrders = Array.isArray(storedOrders) ? storedOrders : [];

        const userEmail = String(currentUser.email).toLowerCase();

        const userOrders = allOrders
            .filter(order =>
                String(order.email || '').toLowerCase() === userEmail
            )
            .sort((a, b) =>
                new Date(b.createdAt || 0) - new Date(a.createdAt || 0)
            );

        profileOrders.innerHTML = '';

        if (userOrders.length === 0) {
            profileOrders.innerHTML = `
                <div class="profile-empty-orders">
                    <span class="material-symbols-outlined">shopping_bag</span>
                    <p>You haven't placed any orders with this account yet.</p>
                    <a href="user-home.html#offers">Explore offers</a>
                </div>
            `;
            return;
        }

        userOrders.forEach(order => {
            const card = document.createElement('article');
            card.className = 'profile-order';

            const orderId = document.createElement('div');
            orderId.className = 'profile-order-id';
            orderId.textContent = order.id || 'Order';

            const date = document.createElement('div');
            date.className = 'profile-order-date';

            const parsedDate = new Date(order.createdAt || '');

            date.textContent =
                order.createdAt && !Number.isNaN(parsedDate.getTime())
                    ? parsedDate.toLocaleString()
                    : 'Date unavailable';

            const info = document.createElement('div');
            info.append(orderId, date);

            const total = document.createElement('div');
            total.className = 'profile-order-total';
            total.textContent = formatPrice(order.total);

            const top = document.createElement('div');
            top.className = 'profile-order-top';
            top.append(info, total);

            const items = Array.isArray(order.items) ? order.items : [];

            const itemCount = items.reduce((sum, item) => {
                return sum + Math.max(1, Number(item.quantity) || 1);
            }, 0);

            const count = document.createElement('span');
            count.textContent = `${itemCount} item(s)`;

            const status = document.createElement('span');
            status.className = 'profile-order-status';
            status.textContent = order.status || 'Pending';

            const bottom = document.createElement('div');
            bottom.className = 'profile-order-bottom';
            bottom.append(count, status);

            card.append(top, bottom);
            profileOrders.appendChild(card);
        });
    }

    profileForm.addEventListener('submit', event => {
        event.preventDefault();

        const firstName = firstNameInput.value.trim();
        const lastName = lastNameInput.value.trim();

        if (firstName.length < 2 || lastName.length < 2) {
            profileMessage.style.color = '#d93030';
            profileMessage.textContent =
                'First and last name must contain at least 2 characters.';
            return;
        }

        currentUser.firstName = firstName;
        currentUser.lastName = lastName;

        localStorage.setItem(
            'chocolifeCurrentUser',
            JSON.stringify(currentUser)
        );

        const storedUsers = readStoredData('chocolifeUsers', []);
        const users = Array.isArray(storedUsers) ? storedUsers : [];

        const userIndex = users.findIndex(user =>
            String(user.email || '').toLowerCase() ===
            String(currentUser.email).toLowerCase()
        );

        if (userIndex !== -1) {
            users[userIndex].firstName = firstName;
            users[userIndex].lastName = lastName;

            localStorage.setItem('chocolifeUsers', JSON.stringify(users));
        }

        renderProfile();

        profileMessage.style.color = '#16834a';
        profileMessage.textContent = 'Profile updated successfully.';
    });

    const logoutButton = document.querySelector('#profile-logout');

    if (logoutButton) {
        logoutButton.addEventListener('click', () => {
            localStorage.removeItem('chocolifeCurrentUser');
            localStorage.removeItem('chocolifeUserRole');
            window.location.href = 'welcome.html';
        });
    }

    function updateCartBadge() {
        const badge = document.querySelector('#cart-badge');

        if (!badge) return;

        const storedCart = readStoredData('chocolifeCart', []);
        const cart = Array.isArray(storedCart) ? storedCart : [];

        badge.textContent = cart.reduce((sum, item) => {
            return sum + (Number(item.quantity) || 1);
        }, 0);
    }

    renderProfile();
    renderOrders();
    updateCartBadge();
}