<?php
// Automatic tests for the API: valid input, invalid input, missing data,
// boundary cases, business rules and permissions.
//
// The tests change the database, so reset it before and after:
//   C:\xampp\php\php.exe database\seed.php
//   C:\xampp\php\php.exe tests\api-tests.php
//   C:\xampp\php\php.exe database\seed.php
//
// Another address can be given as an argument:
//   C:\xampp\php\php.exe tests\api-tests.php http://localhost/chocolife

if (PHP_SAPI !== 'cli') {
    exit('Run this script from the terminal.');
}

$baseUrl = rtrim($argv[1] ?? 'http://localhost/chocolife', '/');

// One "browser" with its own session cookie
class Client
{
    private string $cookieFile;

    public function __construct(private string $baseUrl)
    {
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'choco');
    }

    // Returns [HTTP status, decoded JSON or raw HTML]
    public function request(string $method, string $path, $data = null, bool $multipart = false): array
    {
        $curl = curl_init($this->baseUrl . '/' . $path);

        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEFILE     => $this->cookieFile,
            CURLOPT_COOKIEJAR      => $this->cookieFile,
        ]);

        if ($data !== null) {
            if ($multipart) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            } else {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            }
        }

        $body   = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $json = json_decode((string) $body, true);

        return [$status, $json ?? $body];
    }

    public function login(string $email, string $password): array
    {
        return $this->request('POST', 'api/login.php', ['email' => $email, 'password' => $password]);
    }
}

$passed = 0;
$failed = 0;
$number = 0;

function check(string $name, bool $ok, string $details = ''): void
{
    global $passed, $failed, $number;

    $number++;
    $ok ? $passed++ : $failed++;

    printf("%3d. [%s] %s%s\n", $number, $ok ? 'PASS' : 'FAIL', $name, $ok || $details === '' ? '' : "  -> $details");
}

function productByName(Client $client, string $name): array
{
    [, $result] = $client->request('GET', 'api/products.php?search=' . urlencode($name));
    return $result['products'][0];
}

$guest    = new Client($baseUrl);
$customer = new Client($baseUrl);
$other    = new Client($baseUrl);
$admin    = new Client($baseUrl);

echo "Testing $baseUrl\n\n";

[$status] = $guest->request('GET', 'api/me.php');
if ($status !== 200) {
    exit("The site does not answer at $baseUrl (HTTP $status). Is Apache running?\n");
}

// ---------- Authentication ----------

[$status] = $guest->request('GET', 'api/cart.php');
check('Guest cannot read a cart (401)', $status === 401, "got $status");

[$status, $result] = $guest->request('POST', 'api/register.php',
    ['firstName' => 'Test', 'lastName' => 'User', 'email' => 'not-an-email', 'password' => '12345678']);
check('Register with an invalid email is rejected (400)', $status === 400, "got $status");

[$status] = $guest->request('POST', 'api/register.php',
    ['firstName' => 'Test', 'lastName' => 'User', 'email' => 'test@mail.kz', 'password' => '1234567']);
check('Register with a 7-character password is rejected (boundary, 400)', $status === 400, "got $status");

[$status] = $guest->request('POST', 'api/register.php', ['email' => 'test@mail.kz']);
check('Register with missing fields is rejected (400)', $status === 400, "got $status");

[$status] = $guest->request('POST', 'api/register.php', 'not json');
check('Register with broken JSON is rejected (400)', $status === 400, "got $status");

$email = 'test' . time() . '@mail.kz';
[$status] = $guest->request('POST', 'api/register.php',
    ['firstName' => '<script>alert(1)</script>', 'lastName' => 'Tester', 'email' => $email, 'password' => '12345678']);
check('Register with valid data and an 8-character password works (201)', $status === 201, "got $status");

[$status] = $guest->request('POST', 'api/register.php',
    ['firstName' => 'Test', 'lastName' => 'User', 'email' => strtoupper($email), 'password' => '12345678']);
check('The same email cannot be registered twice, case-insensitive (409)', $status === 409, "got $status");

[$status] = $guest->login('aruzhan@mail.kz', 'wrong-password');
check('Login with a wrong password is rejected (401)', $status === 401, "got $status");

[$status, $result] = $guest->login("' OR 1=1 -- ", 'anything');
check('SQL injection in the login email does not log in (401)', $status === 401, "got $status");

[$status, $result] = $customer->login($email, '12345678');
check('Login of the new user works', $status === 200 && ($result['user']['role'] ?? '') === 'user', "got $status");

[$status, $html] = $customer->request('GET', 'user-home.php');
check('XSS: a name with <script> is shown as text in the header',
    is_string($html) && str_contains($html, '&lt;script&gt;') && !str_contains($html, '<script>alert'));

// ---------- Catalog ----------

[$status, $result] = $guest->request('GET', 'api/products.php?search=spa');
check('Search "spa" finds exactly 1 offer', $status === 200 && $result['total'] === 1, 'got ' . ($result['total'] ?? $status));

[$status, $result] = $guest->request('GET', 'api/products.php?search=' . urlencode("' OR 1=1 -- "));
check('SQL injection in the search returns 0 offers, not all of them', $status === 200 && $result['total'] === 0);

[$status, $result] = $guest->request('GET', 'api/products.php?sort=price_asc&category=3');
$prices = array_column($result['products'] ?? [], 'price');
$sorted = $prices;
sort($sorted);
check('Filter by category + sort by price works', $status === 200 && $prices && $prices === $sorted);

[$status] = $guest->request('GET', 'api/products.php?sort=DROP%20TABLE');
check('Unknown sort value is rejected (400)', $status === 400, "got $status");

[$status, $result] = $guest->request('GET', 'api/products.php?page=999');
check('Page 999 returns the last page instead of an error', $status === 200 && $result['page'] === $result['totalPages']);

// ---------- Cart and checkout ----------

$hotel = productByName($guest, 'Hotel Weekend');
$ski   = productByName($guest, 'Ski Day Pass');

[$status] = $customer->request('POST', 'api/cart.php', ['productId' => $hotel['id'], 'quantity' => 0]);
check('Adding quantity 0 to the cart is rejected (400)', $status === 400, "got $status");

[$status] = $customer->request('POST', 'api/cart.php', ['productId' => $ski['id'], 'quantity' => 1]);
check('An unavailable offer cannot be added to the cart (409)', $status === 409, "got $status");

[$status] = $customer->request('POST', 'api/cart.php', ['productId' => $hotel['id'], 'quantity' => $hotel['stock'] + 1]);
check('Cannot add more coupons than in stock (409)', $status === 409, "got $status");

[$status, $result] = $customer->request('POST', 'api/cart.php', ['productId' => $hotel['id'], 'quantity' => 2]);
check('Adding 2 coupons to the cart works', $status === 200 && $result['count'] === 2, "got $status");

[$status] = $customer->request('PUT', 'api/cart.php', ['productId' => $hotel['id']]);
check('Unsupported HTTP method is rejected (405)', $status === 405, "got $status");

[$status, $result] = $customer->request('POST', 'api/orders.php',
    ['customerName' => 'Test User', 'email' => $email, 'phone' => 'abc', 'address' => 'Almaty']);
check('Checkout with an invalid phone returns a field error (400)', $status === 400 && isset($result['fields']['phone']));

[$status, $result] = $customer->request('POST', 'api/orders.php',
    ['customerName' => 'Test User', 'email' => $email, 'phone' => '+7 701 000 0000', 'address' => 'Almaty, Abay 1']);
$orderId = $result['orderId'] ?? 0;
check('Checkout with valid data creates an order (201)', $status === 201 && $orderId > 0, "got $status");

$hotelAfter = productByName($guest, 'Hotel Weekend');
check('Transaction: the stock went down by 2', $hotelAfter['stock'] === $hotel['stock'] - 2,
    "stock {$hotel['stock']} -> {$hotelAfter['stock']}");

[, $result] = $customer->request('GET', 'api/cart.php');
check('Transaction: the cart is empty after checkout', $result['count'] === 0);

[$status] = $customer->request('POST', 'api/orders.php',
    ['customerName' => 'Test User', 'email' => $email, 'phone' => '+7 701 000 0000', 'address' => 'Almaty, Abay 1']);
check('Checkout with an empty cart is rejected (409)', $status === 409, "got $status");

// ---------- Reviews ----------

$yoga = productByName($guest, 'Yoga Course');

[$status] = $customer->request('POST', 'api/reviews.php', ['productId' => $yoga['id'], 'rating' => 5]);
check('Review of an offer that was not bought is rejected (403)', $status === 403, "got $status");

[$status] = $customer->request('POST', 'api/reviews.php', ['productId' => $hotel['id'], 'rating' => 6]);
check('Rating 6 is rejected (boundary, 400)', $status === 400, "got $status");

[$status] = $customer->request('POST', 'api/reviews.php', ['productId' => $hotel['id'], 'rating' => 5, 'comment' => 'Great!']);
check('Review of a bought offer works', $status === 200, "got $status");

// ---------- Permissions ----------

[$status] = $customer->request('GET', 'api/admin-orders.php');
check('A customer cannot open the admin orders API (403)', $status === 403, "got $status");

[$status] = $customer->request('DELETE', 'api/admin-products.php', ['id' => $hotel['id']]);
check('A customer cannot delete offers (403)', $status === 403, "got $status");

[$status, $html] = $customer->request('GET', 'order-success.php?id=1');
check("A customer cannot open someone else's order (404)", $status === 404, "got $status");

$other->login('daniyar@mail.kz', 'user123');
[, $result] = $other->request('GET', "api/reviews.php?productId={$hotel['id']}");
$myReviewId = null;
foreach ($result['reviews'] as $review) {
    if (str_starts_with($review['author'], '<script>')) {
        $myReviewId = $review['id'];
    }
}
[$status] = $other->request('DELETE', 'api/reviews.php', ['reviewId' => $myReviewId]);
check("A customer cannot delete someone else's review (403)", $myReviewId !== null && $status === 403, "got $status");

// ---------- Admin ----------

[$status, $result] = $admin->login('admin@chocolife.kz', 'admin123');
check('Admin login works', $status === 200 && $result['user']['role'] === 'admin');

[$status] = $admin->request('POST', 'api/cart.php', ['productId' => $hotel['id'], 'quantity' => 1]);
check('An admin cannot buy offers (403)', $status === 403, "got $status");

[$status] = $admin->request('PATCH', 'api/admin-orders.php', ['orderId' => $orderId, 'status' => 'Shipped']);
check('Unknown order status is rejected (400)', $status === 400, "got $status");

[$status] = $admin->request('PATCH', 'api/admin-orders.php', ['orderId' => $orderId, 'status' => 'Declined']);
$hotelDeclined = productByName($guest, 'Hotel Weekend');
check('Declining an order gives the coupons back to the stock',
    $status === 200 && $hotelDeclined['stock'] === $hotel['stock'], "stock {$hotelDeclined['stock']}");

[$status, $result] = $admin->request('GET', 'api/admin-orders.php?status=Declined&from=2026-01-01&to=2030-12-31');
check('Orders can be filtered by status and date range', $status === 200 && $result['count'] >= 1);

[$status] = $admin->request('GET', 'api/admin-orders.php?from=2026-13-45');
check('An invalid date in the filter is rejected (400)', $status === 400, "got $status");

[$status, $result] = $admin->request('POST', 'api/admin-products.php',
    ['id' => '0', 'name' => 'X', 'productNumber' => 'bad number!', 'price' => '-5', 'stock' => '1.5',
     'categoryId' => '999', 'status' => 'Available'], true);
check('Creating an offer with invalid data returns field errors (400)',
    $status === 400 && count($result['fields'] ?? []) >= 4, 'fields: ' . implode(', ', array_keys($result['fields'] ?? [])));

$fakeImage = tempnam(sys_get_temp_dir(), 'img');
file_put_contents($fakeImage, '<?php echo "hacked"; ?>');
[$status, $result] = $admin->request('POST', 'api/admin-products.php',
    ['id' => '0', 'name' => 'Test Offer', 'productNumber' => 'T-' . time(), 'price' => '1000',
     'stock' => '10', 'categoryId' => '1', 'status' => 'Available',
     'image' => new CURLFile($fakeImage, 'image/png', 'shell.php.png')], true);
check('A PHP file disguised as an image is rejected (400)', $status === 400 && isset($result['fields']['image']));

[$status, $result] = $admin->request('POST', 'api/admin-products.php',
    ['id' => '0', 'name' => 'Test Offer', 'productNumber' => 'T-' . time(), 'price' => '1000',
     'oldPrice' => '1500', 'stock' => '10', 'categoryId' => '1', 'status' => 'Available'], true);
$newProductId = $result['id'] ?? 0;
check('Creating a valid offer works', $status === 200 && $newProductId > 0, "got $status");

[$status] = $admin->request('DELETE', 'api/admin-products.php', ['id' => $newProductId]);
check('Deleting an offer works', $status === 200, "got $status");

[$status] = $admin->request('DELETE', 'api/admin-products.php', ['id' => $newProductId]);
check('Deleting the same offer again returns 404', $status === 404, "got $status");

[$status] = $admin->request('DELETE', 'api/admin-users.php', ['id' => 1]);
check('Admin accounts cannot be deleted (403)', $status === 403, "got $status");

// ---------- Logout ----------

$customer->request('POST', 'api/logout.php');
[, $result] = $customer->request('GET', 'api/me.php');
check('After logout the session is gone', $result['user'] === null);

[$status] = $customer->request('GET', 'api/orders.php');
check('After logout the orders API answers 401', $status === 401, "got $status");

echo "\n$passed passed, $failed failed.\n";
echo "Reset the test data now: C:\\xampp\\php\\php.exe database\\seed.php\n";

exit($failed > 0 ? 1 : 0);
