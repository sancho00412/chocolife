<?php
require __DIR__ . '/includes/page.php';

$user = requirePageLogin();

$pageTitle   = 'Feedback';
$activePage  = 'feedback';
$pageCss     = ['user-home.css', 'help.css'];
$pageScripts = ['help.js'];

require __DIR__ . '/includes/header.php';
?>

    <main class="help-page">
        <section class="help-intro">
            <p class="eyebrow">HELP</p>
            <h1>Feedback</h1>
            <p>For questions and comments. We usually answer within one working day.</p>
            <p>Need help right now? Call us:
                <b><?= SUPPORT_PHONE ?></b></p>
        </section>

        <section class="help-card">
            <form id="message-form" class="help-form" data-type="feedback" novalidate>
                <div class="help-form-row">
                    <div class="form-field">
                        <label for="name">Your name *</label>
                        <input type="text" id="name" maxlength="100" required
                            value="<?= e($user['firstName'] . ' ' . $user['lastName']) ?>">
                        <small class="field-error" id="name-error"></small>
                    </div>

                    <div class="form-field">
                        <label for="email">Email *</label>
                        <input type="email" id="email" maxlength="100" required value="<?= e($user['email']) ?>">
                        <small class="field-error" id="email-error"></small>
                    </div>
                </div>

                <div class="form-field">
                    <label for="subject">Subject *</label>
                    <select id="subject" required>
                        <option value="">Choose a subject</option>
                        <option>Question about an order</option>
                        <option>Problem with a coupon</option>
                        <option>Suggestion</option>
                        <option>Other</option>
                    </select>
                    <small class="field-error" id="subject-error"></small>
                </div>

                <div class="form-field">
                    <label for="message">Message *</label>
                    <textarea id="message" rows="6" maxlength="2000" required
                        placeholder="Tell us what happened or what you would like to improve"></textarea>
                    <small class="field-error" id="message-error"></small>
                </div>

                <p id="form-status" class="form-status" role="status"></p>

                <button type="submit" id="send-button">Send message</button>
            </form>
        </section>
    </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
