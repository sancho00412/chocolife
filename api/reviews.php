<?php
// GET    ?productId=5                        -> reviews of the offer
// POST   {productId, rating, comment}        -> add or update my review
// DELETE {reviewId}                          -> delete a review (author or admin)

require __DIR__ . '/../includes/api.php';

$user = requireLogin();
$db = getDb();

// Did this user buy the offer? Declined orders do not count
function hasBought(PDO $db, int $userId, int $productId): bool
{
    $stmt = $db->prepare("
        SELECT 1
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        WHERE o.user_id = ? AND oi.product_id = ? AND o.status <> 'Declined'
        LIMIT 1
    ");
    $stmt->execute([$userId, $productId]);

    return (bool) $stmt->fetchColumn();
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $productId = (int) ($_GET['productId'] ?? 0);

        $stmt = $db->prepare('
            SELECT r.id, r.user_id, r.rating, r.comment, r.created_at,
                   u.first_name, u.last_name
            FROM reviews r
            JOIN users u ON u.id = r.user_id
            WHERE r.product_id = ?
            ORDER BY r.created_at DESC, r.id DESC
        ');
        $stmt->execute([$productId]);

        $reviews  = [];
        $myReview = null;

        foreach ($stmt->fetchAll() as $row) {
            $isMine = (int) $row['user_id'] === $user['id'];

            $review = [
                'id'        => (int) $row['id'],
                // Only the first name and the first letter of the last name are shown
                'author'    => $row['first_name'] . ' ' . mb_substr($row['last_name'], 0, 1) . '.',
                'rating'    => (int) $row['rating'],
                'comment'   => $row['comment'],
                'date'      => date('d.m.Y', strtotime($row['created_at'])),
                'isMine'    => $isMine,
                'canDelete' => $isMine || $user['role'] === 'admin',
            ];

            if ($isMine) {
                $myReview = $review;
            }

            $reviews[] = $review;
        }

        $count   = count($reviews);
        $average = $count ? round(array_sum(array_column($reviews, 'rating')) / $count, 1) : 0;

        sendJson([
            'success'   => true,
            'reviews'   => $reviews,
            'count'     => $count,
            'average'   => $average,
            'canReview' => $user['role'] === 'user' && hasBought($db, $user['id'], $productId),
            'isAdmin'   => $user['role'] === 'admin',
            'myReview'  => $myReview,
        ]);

    case 'POST':
        $data      = readJsonBody();
        $productId = (int) ($data['productId'] ?? 0);
        $rating    = (int) ($data['rating'] ?? 0);
        $comment   = field($data, 'comment');

        if ($rating < 1 || $rating > 5) {
            sendError('Please choose a rating from 1 to 5.');
        }
        if (mb_strlen($comment) > 1000) {
            sendError('Comment must be at most 1000 characters.');
        }
        if (!hasBought($db, $user['id'], $productId)) {
            sendError('Only customers who bought this offer can leave a review.', 403);
        }

        // One review per user and offer: a second save updates the old review
        $stmt = $db->prepare('
            INSERT INTO reviews (user_id, product_id, rating, comment)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                rating = VALUES(rating),
                comment = VALUES(comment),
                created_at = CURRENT_TIMESTAMP
        ');
        $stmt->execute([$user['id'], $productId, $rating, $comment === '' ? null : $comment]);

        sendJson(['success' => true]);

    case 'DELETE':
        $data     = readJsonBody();
        $reviewId = (int) ($data['reviewId'] ?? 0);

        $stmt = $db->prepare('SELECT user_id FROM reviews WHERE id = ?');
        $stmt->execute([$reviewId]);
        $authorId = $stmt->fetchColumn();

        if ($authorId === false) {
            sendError('Review not found.', 404);
        }

        // Users can delete only their own reviews, admins can delete any review
        if ((int) $authorId !== $user['id'] && $user['role'] !== 'admin') {
            sendError('You can delete only your own reviews.', 403);
        }

        $db->prepare('DELETE FROM reviews WHERE id = ?')->execute([$reviewId]);

        sendJson(['success' => true]);

    default:
        sendError('Method not allowed.', 405);
}
