<?php
// koneksi.php
date_default_timezone_set('Asia/Jakarta');

$host     = "localhost";
$user     = "root";
$password = "";
$database = "rohis";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>