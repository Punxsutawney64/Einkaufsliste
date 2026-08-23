<?php
declare(strict_types=1);

session_set_cookie_params([
    'httponly' => true,
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();

$config = require dirname(__DIR__) . '/config.php';

function db(): PDO
{
    static $pdo;
    global $config;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $db = $config['db'];
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $db['host'], $db['port'], $db['name']);
    $pdo = new PDO($dsn, $db['user'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function isAdmin(): bool
{
    return (bool) (user()['admin'] ?? false);
}

function csrfToken(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function requireCsrf(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(419);
        exit('Ungültige oder abgelaufene Anfrage. Bitte Seite neu laden.');
    }
}

function requireLogin(): void
{
    if (!user()) {
        redirect('index.php');
    }
}

function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        exit('Diese Funktion ist Administratoren vorbehalten.');
    }
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function takeFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function postInt(string $key): int
{
    return filter_var($_POST[$key] ?? null, FILTER_VALIDATE_INT) ?: 0;
}

function uniqueName(string $table, string $name, int $excludeId = 0): bool
{
    $allowed = ['SECTION', 'HOME_LOCATION', 'ARTICLE'];
    if (!in_array($table, $allowed, true)) {
        throw new InvalidArgumentException('Ungültige Tabelle');
    }
    $sql = "SELECT COUNT(*) FROM `$table` WHERE LOWER(`NAME`) = LOWER(?)";
    $params = [$name];
    if ($excludeId > 0) {
        $sql .= ' AND ID <> ?';
        $params[] = $excludeId;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn() === 0;
}

