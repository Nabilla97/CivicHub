
<?php

$servername = getenv('DB_HOST') ?: 'db';
$database = getenv('DB_NAME') ?: 'db_civichub';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';

$conn = mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
    die('Koneksi database gagal: ' . mysqli_connect_error());
}

return $conn;
