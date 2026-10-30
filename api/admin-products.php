<?php
// Admin only.
// POST   multipart form (id = 0 creates, id > 0 updates) -> save an offer with an optional image
// DELETE {id}                                            -> delete an offer

require __DIR__ . '/../includes/api.php';

requireAdmin();
$db = getDb();

const IMAGE_DIR      = __DIR__ . '/../images/products/';
const IMAGE_URL      = 'images/products/';
const MAX_IMAGE_SIZE = 2 * 1024 * 1024;
const IMAGE_TYPES    = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

// Returns a trimmed form field or '' if it is missing or not a string
function postField(string $key): string
{
    return is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
}

// Deletes an uploaded image file; images from other folders are never touched
function deleteImage(?string $path): void
{
    if ($path && str_starts_with($path, IMAGE_URL)) {
        $file = IMAGE_DIR . basename($path);

        if (is_file($file)) {
            unlink($file);
        }
    }
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        $id          = (int) ($_POST['id'] ?? 0);
        $name        = postField('name');
        $number      = postField('productNumber');
        $description = postField('description');
        $price       = postField('price');
        $oldPrice    = postField('oldPrice');
        $stock       = postField('stock');
        $maxPerCustomer = postField('maxPerCustomer');
        $categoryId  = (int) ($_POST['categoryId'] ?? 0);
        $partnerId   = (int) ($_POST['partnerId'] ?? 0);
        $validUntil  = postField('validUntil');
        $useUntil    = postField('useUntil');
        $status      = postField('status');
        $removeImage = postField('removeImage') === '1';

        $existing = null;

        if ($id > 0) {
            $stmt = $db->prepare('SELECT id, image, image_credit, image_source FROM products WHERE id = ?');
            $stmt->execute([$id]);
            $existing = $stmt->fetch();

            if (!$existing) {
                sendError('Offer not found.', 404);
            }
        }

        // Server-side validation, errors are returned per field
        $errors = [];

        if (mb_strlen($name) < 3 || mb_strlen($name) > 150) {
            $errors['name'] = 'Name must be 3-150 characters.';
        }

        if (!preg_match('/^[A-Za-z0-9-]{1,20}$/', $number)) {
            $errors['productNumber'] = 'Use 1-20 letters, digits or "-".';
        } else {
            $stmt = $db->prepare('SELECT id FROM products WHERE product_number = ? AND id <> ?');
            $stmt->execute([$number, $id]);

            if ($stmt->fetch()) {
                $errors['productNumber'] = 'Another offer already has this number.';
            }
        }

        if (mb_strlen($description) > 2000) {
            $errors['description'] = 'Description must be at most 2000 characters.';
        }

        if (!is_numeric($price) || $price < 0 || $price > 10000000) {
            $errors['price'] = 'Price must be a number from 0 to 10,000,000.';
        }

        if ($oldPrice !== '' && (!is_numeric($oldPrice) || (is_numeric($price) && $oldPrice < $price))) {
            $errors['oldPrice'] = 'Old price must be a number not lower than the price.';
        }

        if (!ctype_digit($stock) || $stock > 100000) {
            $errors['stock'] = 'Stock must be a whole number from 0 to 100,000.';
        }

        if (!preg_match('/^([1-9]|10)$/', $maxPerCustomer)) {
            $errors['maxPerCustomer'] = 'Limit must be a whole number from 1 to 10.';
        }

        $stmt = $db->prepare('SELECT id FROM categories WHERE id = ?');
        $stmt->execute([$categoryId]);
        if (!$stmt->fetch()) {
            $errors['categoryId'] = 'Choose a category.';
        }

        if ($partnerId > 0) {
            $stmt = $db->prepare('SELECT id FROM partners WHERE id = ?');
            $stmt->execute([$partnerId]);
            if (!$stmt->fetch()) {
                $errors['partnerId'] = 'Unknown partner.';
            }
        }

        foreach (['validUntil' => $validUntil, 'useUntil' => $useUntil] as $field => $date) {
            if ($date !== '') {
                $parsed = DateTime::createFromFormat('Y-m-d', $date);
                if (!$parsed || $parsed->format('Y-m-d') !== $date) {
                    $errors[$field] = 'Enter a valid date.';
                }
            }
        }

        if (!isset($errors['validUntil']) && !isset($errors['useUntil'])
            && $validUntil !== '' && $useUntil !== '' && $useUntil < $validUntil) {
            $errors['useUntil'] = 'The coupon cannot expire before the sale ends.';
        }

        if (!in_array($status, ['Available', 'Unavailable'], true)) {
            $errors['status'] = 'Choose a status.';
        }

        // Image upload: real file type is checked by its content, not by its name
        $upload = $_FILES['image'] ?? null;
        $newImageExtension = null;

        if ($upload && $upload['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($upload['error'] !== UPLOAD_ERR_OK) {
                $errors['image'] = 'The image could not be uploaded.';
            } elseif ($upload['size'] > MAX_IMAGE_SIZE) {
                $errors['image'] = 'The image must be at most 2 MB.';
            } else {
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
                $newImageExtension = IMAGE_TYPES[$mime] ?? null;

                if ($newImageExtension === null) {
                    $errors['image'] = 'Only JPG, PNG or WEBP images are allowed.';
                }
            }
        }

        if ($errors) {
            sendJson(['success' => false, 'error' => 'Please fix the errors in the form.', 'fields' => $errors], 400);
        }

        $image       = $existing['image'] ?? null;
        $imageCredit = $existing['image_credit'] ?? null;
        $imageSource = $existing['image_source'] ?? null;

        if ($newImageExtension !== null) {
            if (!is_dir(IMAGE_DIR)) {
                mkdir(IMAGE_DIR, 0755, true);
            }

            // Random file name: the user cannot choose a path or overwrite files
            $fileName = bin2hex(random_bytes(8)) . '.' . $newImageExtension;

            if (!move_uploaded_file($upload['tmp_name'], IMAGE_DIR . $fileName)) {
                sendError('The image could not be saved.', 500);
            }

            deleteImage($image);
            $image = IMAGE_URL . $fileName;
            // The admin's own photo: the old author credit does not belong to it
            $imageCredit = $imageSource = null;
        } elseif ($removeImage) {
            deleteImage($image);
            $image = $imageCredit = $imageSource = null;
        }

        $values = [
            $number,
            $name,
            $description === '' ? null : $description,
            $price,
            $oldPrice === '' ? null : $oldPrice,
            (int) $stock,
            (int) $maxPerCustomer,
            $status,
            $image,
            $imageCredit,
            $imageSource,
            $validUntil === '' ? null : $validUntil,
            $useUntil === '' ? null : $useUntil,
            $categoryId,
            $partnerId > 0 ? $partnerId : null,
        ];

        if ($existing) {
            $stmt = $db->prepare('
                UPDATE products
                SET product_number = ?, name = ?, description = ?, price = ?, old_price = ?,
                    stock = ?, max_per_customer = ?, status = ?,
                    image = ?, image_credit = ?, image_source = ?,
                    valid_until = ?, use_until = ?, category_id = ?, partner_id = ?
                WHERE id = ?
            ');
            $stmt->execute([...$values, $id]);
        } else {
            $stmt = $db->prepare('
                INSERT INTO products (product_number, name, description, price, old_price,
                                      stock, max_per_customer, status, image, image_credit, image_source,
                                      valid_until, use_until, category_id, partner_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute($values);
            $id = (int) $db->lastInsertId();
        }

        sendJson(['success' => true, 'id' => $id, 'created' => $existing === null]);

    case 'DELETE':
        $id = (int) (readJsonBody()['id'] ?? 0);

        $stmt = $db->prepare('SELECT image FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if (!$product) {
            sendError('Offer not found.', 404);
        }

        // Cart items, reviews and favorites are deleted by ON DELETE CASCADE,
        // old order items keep their name and price (product_id becomes NULL)
        $db->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
        deleteImage($product['image']);

        sendJson(['success' => true]);

    default:
        sendError('Method not allowed.', 405);
}
