<?php
// Business rules for buying coupons

// Coupons of the offer that the user has already bought. Declined orders do not count
function boughtQuantity(PDO $db, int $userId, int $productId): int
{
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(oi.quantity), 0)
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        WHERE o.user_id = ? AND oi.product_id = ? AND o.status <> 'Declined'
    ");
    $stmt->execute([$userId, $productId]);

    return (int) $stmt->fetchColumn();
}

// How many coupons the user may have in the cart right now.
// Three limits: coupons in stock, the offer limit per customer
// (minus what was bought before) and 99 as a technical maximum
function maxQuantity(int $stock, int $maxPerCustomer, int $alreadyBought): int
{
    return max(0, min(99, $stock, $maxPerCustomer - $alreadyBought));
}

// Explains to the user why the wanted quantity is not allowed
function limitMessage(string $name, int $stock, int $maxPerCustomer, int $alreadyBought): string
{
    if ($alreadyBought >= $maxPerCustomer) {
        return "You have already bought the maximum of $maxPerCustomer coupon(s) of \"$name\" per customer.";
    }

    if ($maxPerCustomer - $alreadyBought <= $stock) {
        $left = $maxPerCustomer - $alreadyBought;

        return $alreadyBought > 0
            ? "\"$name\" is limited to $maxPerCustomer coupon(s) per customer. You bought $alreadyBought before, so you can add $left more."
            : "\"$name\" is limited to $maxPerCustomer coupon(s) per customer.";
    }

    return $stock > 0
        ? "Only $stock coupon(s) of \"$name\" left."
        : "\"$name\" is sold out.";
}
