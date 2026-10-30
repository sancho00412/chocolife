<?php
require __DIR__ . '/includes/page.php';

$user = requirePageLogin();

// [question, answer]
$questions = [
    'Buying' => [
        ['How do I buy a coupon?',
         'Open an offer, choose the number of coupons and press "Add to Cart". Then open the cart, press "Proceed to Checkout", fill in your contacts and place the order.'],
        ['How many coupons can I buy?',
         'Every offer has a limit per customer. It is 2 for things people usually do together (cinema, dinner, bowling) and 1 for personal services (salon, gym, hotel). Coupons from your earlier orders count too.'],
        ['Why can\'t I add an offer to the cart?',
         'The offer may be sold out, unavailable, or you have already reached the limit per customer. The reason is shown on the offer page instead of the button.'],
    ],
    'Using a coupon' => [
        ['How long is a coupon valid?',
         'The offer page shows two dates: until when the coupon can be bought and until when it can be used. Please use your coupon before the second date.'],
        ['Where can I see my orders?',
         'Open "My Profile" and find the "My Orders" block. Click an order to see the offers in it.'],
        ['What do the order statuses mean?',
         '"Pending" means the order is waiting for confirmation, "Completed" means it is confirmed, "Declined" means it was cancelled and the coupons were returned.'],
    ],
    'Account' => [
        ['How can I leave a review?',
         'Only customers who bought an offer can review it. After your order the review form appears on the offer page.'],
        ['How do I save an offer for later?',
         'Press "Save" on the offer page. All saved offers are on the "Favorites" page in the header.'],
        ['How do I change my name or password?',
         'Open "My Profile". You can change your name and phone there and set a new password.'],
    ],
];

$pageTitle   = 'Frequently Asked Questions';
$activePage  = 'faq';
$pageCss     = ['user-home.css', 'help.css'];
$pageScripts = [];

require __DIR__ . '/includes/header.php';
?>

    <main class="help-page">
        <section class="help-intro">
            <p class="eyebrow">HELP</p>
            <h1>Frequently asked questions</h1>
            <p>Questions and answers from our users. Did not find an answer? <a href="feedback.php">Write to us</a>.</p>
        </section>

<?php foreach ($questions as $group => $items): ?>
        <section class="help-card">
            <h2><?= e($group) ?></h2>
<?php foreach ($items as [$question, $answer]): ?>
            <details class="faq-item">
                <summary><?= e($question) ?></summary>
                <p><?= e($answer) ?></p>
            </details>
<?php endforeach; ?>
        </section>
<?php endforeach; ?>
    </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
