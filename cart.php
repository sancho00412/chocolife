<?php
require __DIR__ . '/includes/page.php';

$user = requirePageUser();

$pageTitle   = 'Shopping Cart';
$activePage  = 'cart';
$pageCss     = ['user-home.css', 'cart.css'];
$pageScripts = ['cart.js'];

require __DIR__ . '/includes/header.php';
?>

    <main class="cart-page">
        <a href="user-home.php#offers" class="back-link">← Continue Shopping</a>

        <h1>Shopping Cart</h1>
        <p class="cart-subtitle">Review your selected offers.</p>

        <p id="cart-status" class="page-status" role="status">Loading your cart...</p>

        <div class="cart-layout">
            <section aria-label="Cart items">
                <div id="cart-items"></div>
            </section>

            <aside class="cart-summary">
                <h2>Order Summary</h2>

                <div class="summary-row">
                    <span>Items</span>
                    <span id="cart-count">0</span>
                </div>

                <div class="summary-row total-row">
                    <span>Total</span>
                    <span id="cart-total">0 ₸</span>
                </div>

                <a id="checkout-button" href="checkout.php" class="button-link">
                    Proceed to Checkout
                </a>
            </aside>
        </div>
    </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
