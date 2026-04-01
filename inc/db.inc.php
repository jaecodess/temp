<?php
/*
 * Database connection helper.
 * Reads credentials from /var/www/private/db-config.ini on the server.
 * Falls back to localhost defaults for local development.
 */
function getDbConnection()
{
    $config = @parse_ini_file('/var/www/private/db-config.ini');

    if ($config) {
        // --- CLOUD SERVER SETTINGS ---
        $servername = $config['servername'];
        $username   = $config['username'];
        $password   = $config['password'];
        $dbname     = $config['dbname'];
        $port       = isset($config['port']) ? intval($config['port']) : 3306;
    } else {
        // --- LOCAL DEVELOPMENT SETTINGS ---
        // Credentials are stored in .env (gitignored).
        $config = parse_ini_file(__DIR__ . '/../.env');
        $servername = $config['servername'];
        $username   = $config['username'];
        $password   = $config['password'];
        $dbname     = $config['dbname'];
        $port       = isset($config['port']) ? intval($config['port']) : 3306;
    }

    $conn = new mysqli($servername, $username, $password, $dbname, $port);

    if ($conn->connect_error) {
        error_log("DB connection failed: " . $conn->connect_error);
        die("A database error occurred. Please try again later.");
    }

    return $conn;
}
