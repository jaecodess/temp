<?php
/*
 * PDO connection for PHPAuth.
 * Reads credentials from /var/www/private/db-config.ini on the server.
 * Falls back to .env at the project root for local development.
 * Used exclusively by PHPAuth — all other queries use inc/db.inc.php (MySQLi).
 */
function getPHPAuthDbConnection(): PDO
{
    $config = @parse_ini_file('/var/www/private/db-config.ini');

    if ($config === false) {
        $config = parse_ini_file(__DIR__ . '/../.env');
        if ($config === false) {
            error_log("PHPAuth DB: failed to load credentials from .env");
            die("A database error occurred. Please try again later.");
        }
    }

    $host   = $config['servername'];
    $user   = $config['username'];
    $pass   = $config['password'];
    $dbname = $config['dbname'];
    $port   = isset($config['port']) ? intval($config['port']) : 3306;

    try {
        return new PDO(
            "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    } catch (PDOException $e) {
        error_log("PHPAuth DB connection failed: " . $e->getMessage());
        die("A database error occurred. Please try again later.");
    }
}
