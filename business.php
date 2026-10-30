<?php
require __DIR__ . '/includes/page.php';

$user = requirePageLogin();

// Real numbers from the database for the "Why Chocolife" block
$stats = getDb()->query("
    SELECT
        (SELECT COUNT(*) FROM users WHERE role = 'user') AS customers,
        (SELECT COUNT(*) FROM partners)                  AS partners,
        (SELECT COALESCE(SUM(oi.quantity), 0)
           FROM order_items oi
           JOIN orders o ON o.id = oi.order_id
          WHERE o.status <> 'Declined')                  AS coupons
")->fetch();

$pageTitle   = 'For Business';
$activePage  = 'business';
$pageCss     = ['user-home.css', 'help.css'];
$pageScripts = ['help.js'];

require __DIR__ . '/includes/header.php';
?>

    <main class="help-page">
        <section class="help-intro">
            <p class="eyebrow">FOR BUSINESS</p>
            <h1>Place your offer on Chocolife</h1>
            <p>Restaurants, salons, fitness clubs, entertainment and travel companies find new customers with us.</p>
            <p>Questions about partnership? Call us:
                <b><?= SUPPORT_PHONE ?></b></p>
        </section>

        <section class="business-stats">
            <div>
                <strong><?= (int) $stats['customers'] ?></strong>
                <span>customers</span>
            </div>
            <div>
                <strong><?= (int) $stats['partners'] ?></strong>
                <span>partners</span>
            </div>
            <div>
                <strong><?= (int) $stats['coupons'] ?></strong>
                <span>coupons sold</span>
            </div>
        </section>

        <section class="help-card business-steps">
            <h2>How it works</h2>
            <ol>
                <li><b>Send a request</b> with the form below.</li>
                <li><b>We call you</b> and prepare the offer together: price, discount, limits and dates.</li>
                <li><b>The offer goes live</b>, customers buy coupons and come to you.</li>
            </ol>
        </section>

        <section class="help-card">
            <h2>Partner request</h2>

            <form id="message-form" class="help-form" data-type="business" novalidate>
                <div class="help-form-row">
                    <div class="form-field">
                        <label for="company">Company *</label>
                        <input type="text" id="company" maxlength="150" required placeholder="Company or brand name">
                        <small class="field-error" id="company-error"></small>
                    </div>

                    <div class="form-field">
                        <label for="name">Contact person *</label>
                        <input type="text" id="name" maxlength="100" required
                            value="<?= e($user['firstName'] . ' ' . $user['lastName']) ?>">
                        <small class="field-error" id="name-error"></small>
                    </div>
                </div>

                <div class="help-form-row">
                    <div class="form-field">
                        <label for="phone">Phone *</label>
                        <input type="tel" id="phone" maxlength="20" required placeholder="+7 700 123 45 67">
                        <small class="field-error" id="phone-error"></small>
                    </div>

                    <div class="form-field">
                        <label for="email">Email *</label>
                        <input type="email" id="email" maxlength="100" required value="<?= e($user['email']) ?>">
                        <small class="field-error" id="email-error"></small>
                    </div>
                </div>

                <div class="form-field">
                    <label for="message">About your business *</label>
                    <textarea id="message" rows="5" maxlength="2000" required
                        placeholder="What do you offer, where are you located, what discount can you give?"></textarea>
                    <small class="field-error" id="message-error"></small>
                </div>

                <p id="form-status" class="form-status" role="status"></p>

                <button type="submit" id="send-button">Send request</button>
            </form>
        </section>
    </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
