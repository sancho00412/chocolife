<?php
require __DIR__ . '/includes/page.php';

$user = requirePageUser();

// The form is filled with the data from the profile
$stmt = getDb()->prepare('SELECT phone FROM users WHERE id = ?');
$stmt->execute([$user['id']]);
$phone = $stmt->fetchColumn() ?: '';

$pageTitle   = 'Checkout';
$activePage  = 'cart';
$pageCss     = ['user-home.css', 'checkout.css'];
$pageScripts = ['checkout.js'];

require __DIR__ . '/includes/header.php';
?>

    <main class="checkout-page">
        <a href="cart.php" class="back-link">← Back to Cart</a>

        <h1>Checkout</h1>
        <p class="page-description">Complete your order details below.</p>

        <div class="checkout-layout">

            <section class="checkout-card">
                <h2>Contact Information</h2>

                <form id="checkout-form" novalidate>
                    <div class="form-group">
                        <label for="customer-name">Full Name</label>
                        <input type="text" id="customer-name" name="customerName"
                            value="<?= e($user['firstName'] . ' ' . $user['lastName']) ?>"
                            placeholder="Enter your full name" maxlength="100" required>
                        <small class="field-error" id="customerName-error"></small>
                    </div>

                    <div class="form-group">
                        <label for="customer-email">Email</label>
                        <input type="email" id="customer-email" name="email"
                            value="<?= e($user['email']) ?>"
                            placeholder="example@email.com" maxlength="100" required>
                        <small class="field-error" id="email-error"></small>
                    </div>

                    <div class="form-group">
                        <label for="customer-phone">Phone Number</label>
                        <input type="tel" id="customer-phone" name="phone"
                            value="<?= e($phone) ?>"
                            placeholder="+7 700 123 45 67" maxlength="20" required>
                        <small class="field-error" id="phone-error"></small>
                    </div>

                    <div class="form-group">
                        <label for="customer-address">Address</label>
                        <textarea id="customer-address" name="address" rows="3"
                            placeholder="Enter your address" maxlength="200" required></textarea>
                        <small class="field-error" id="address-error"></small>
                    </div>

                    <button type="submit" id="place-order-button">Place Order</button>

                    <p id="checkout-message" role="status"></p>
                </form>
            </section>

            <aside class="checkout-summary">
                <h2>Your Order</h2>

                <div id="checkout-items"><p class="page-status">Loading...</p></div>

                <div class="summary-total">
                    <span>Total</span>
                    <span id="checkout-total">0 ₸</span>
                </div>
            </aside>

        </div>
    </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
