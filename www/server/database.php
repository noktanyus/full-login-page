<?php

$dbservername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbdatabase = "nokchat";
$key = "7D@Fj1$8lN^yIi%gV#sZpX!hO&rL+e5BwQ9u0mH3v2bUfGcA*oKzC6YdRtPqMn4WxS;T,~J`@E!a{9G*}LH#p(dO7B)p0K]";
// Bağlantı oluştur
$conn = mysqli_connect($dbservername, $dbusername, $dbpassword, $dbdatabase);

// Bağlantı kontrolü
if (!$conn) {
    die("Veritabanına bağlantı hatası: " . mysqli_connect_error());
}





?>