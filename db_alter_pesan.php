<?php
$conn = new mysqli('localhost', 'root', '', 'rohis');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->query("ALTER TABLE absensi ADD COLUMN pesan TEXT DEFAULT NULL");
echo "Column added successfully";
?>
