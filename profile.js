// Profile page: personal data, password, orders and saved offers

const profileForm = document.querySelector('#profile-form');
const profileMessage = document.querySelector('#profile-message');
const passwordForm = document.querySelector('#password-form');
const passwordMessage = document.querySelector('#password-message');
const profileOrders = document.querySelector('#profile-orders');
const ordersStatus = document.querySelector('#orders-status');
const profileFavorites = document.querySelector('#profile-favorites');
const favoritesStatus = document.querySelector('#favorites-status');

function showStatus(element, text, isError) {
    element.textContent = text;
    element.classList.toggle('error', isError);
    element.classList.toggle('success', !isError && text !== '');
}

profileForm.addEventListener('submit', async event => {
    event.preventDefault();

    const firstName = document.querySelector('#profile-first-name').value.trim();
    const lastName = document.querySelector('#profile-last-name').value.trim();
    const phone = document.querySelector('#profile-phone').value.trim();

    if (firstName.length < 2 || lastName.length < 2) {
        showStatus(profileMessage, 'First and last name must contain at least 2 characters.', true);
        return;
    }

    if (phone !== '' && !/^\+?[\d\s()-]{10,20}$/.test(phone)) {
        showStatus(profileMessage, 'Enter a valid phone number or leave it empty.', true);
        return;
    }

    try {
        const result = await requestJson('api/profile.php', 'PATCH', { firstName, lastName, phone });

        if (!result.success) {
            showStatus(profileMessage, result.error, true);
            return;
        }

        // Update the name on the page without reloading
        const fullName = `${result.firstName} ${result.lastName}`;
        document.querySelector('#profile-display-name').textContent = fullName;
        document.querySelector('#user-name').textContent = fullName;

        showStatus(profileMessage, 'Profile updated successfully.', false);
    } catch {
        showStatus(profileMessage, 'Could not connect to the server.', true);
    }
});

passwordForm.addEventListener('submit', async event => {
    event.preventDefault();

    const currentPassword = document.querySelector('#current-password').value;
    const newPassword = document.querySelector('#new-password').value;
    const confirmPassword = document.querySelector('#confirm-new-password').value;

    if (!currentPassword) {
        showStatus(passwordMessage, 'Enter your current password.', true);
        return;
    }
    if (newPassword.length < 8) {
        showStatus(passwordMessage, 'New password must be at least 8 characters.', true);
        return;
    }
    if (newPassword !== confirmPassword) {
        showStatus(passwordMessage, 'New passwords do not match.', true);
        return;
    }

    try {
        const result = await requestJson('api/profile.php', 'POST', { currentPassword, newPassword });

        if (!result.success) {
            showStatus(passwordMessage, result.error, true);
            return;
        }

        passwordForm.reset();
        showStatus(passwordMessage, 'Password changed successfully.', false);
    } catch {
        showStatus(passwordMessage, 'Could not connect to the server.', true);
    }
});

async function loadOrders() {
    try {
        const result = await requestJson('api/orders.php');

        if (!result.success) {
            throw new Error(result.error);
        }

        profileOrders.innerHTML = '';

        if (result.orders.length === 0) {
            ordersStatus.innerHTML =
                'You have no orders yet. <a href="user-home.php#offers">Explore offers</a>';
            return;
        }

        ordersStatus.hidden = true;

        result.orders.forEach(order => {
            const card = document.createElement('details');
            card.className = 'profile-order';

            const summary = document.createElement('summary');

            const top = document.createElement('div');
            top.className = 'profile-order-top';

            const info = document.createElement('div');

            const number = document.createElement('div');
            number.className = 'profile-order-id';
            number.textContent = order.orderNumber;

            const date = document.createElement('div');
            date.className = 'profile-order-date';
            date.textContent = order.date;

            info.append(number, date);

            const total = document.createElement('div');
            total.className = 'profile-order-total';
            total.textContent = formatPrice(order.total);

            top.append(info, total);

            const bottom = document.createElement('div');
            bottom.className = 'profile-order-bottom';

            const count = document.createElement('span');
            count.textContent = `${order.itemCount} item(s) · click for details`;

            const status = document.createElement('span');
            status.className = `profile-order-status status-${order.status.toLowerCase()}`;
            status.textContent = order.status;

            bottom.append(count, status);
            summary.append(top, bottom);

            const items = document.createElement('ul');
            items.className = 'profile-order-items';

            order.items.forEach(item => {
                const line = document.createElement('li');
                line.textContent =
                    `${item.name} — ${item.quantity} × ${formatPrice(item.price)}`;
                items.appendChild(line);
            });

            card.append(summary, items);
            profileOrders.appendChild(card);
        });
    } catch {
        showStatus(ordersStatus, 'Could not load your orders.', true);
    }
}

async function loadFavorites() {
    try {
        const result = await requestJson('api/favorites.php');

        if (!result.success) {
            throw new Error(result.error);
        }

        profileFavorites.innerHTML = '';

        if (result.favorites.length === 0) {
            favoritesStatus.textContent = 'No saved offers yet. Press "Save" on an offer page.';
            return;
        }

        favoritesStatus.hidden = true;

        result.favorites.forEach(favorite => {
            const item = document.createElement('li');

            const link = document.createElement('a');
            link.href = `offer.php?id=${favorite.id}`;
            link.textContent = favorite.name;

            const price = document.createElement('span');
            price.textContent = `${favorite.category} · ${formatPrice(favorite.price)}`;

            item.append(link, price);
            profileFavorites.appendChild(item);
        });
    } catch {
        showStatus(favoritesStatus, 'Could not load saved offers.', true);
    }
}

// Orders and favorites exist only for customers
if (profileOrders) {
    loadOrders();
    loadFavorites();
}
