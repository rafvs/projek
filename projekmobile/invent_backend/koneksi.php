<?php
$conn = mysqli_connect("localhost", "root", "", "barang"); 
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
