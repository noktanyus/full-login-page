<?php
session_start();

// Oturum kontrolü
if (isset($_SESSION['user_id'])) {
    // Oturum var, oturumu sonlandır
    session_destroy();

    // Oturum ile ilişkili cookie'yi sil
    $cookie_name = "session_id";
    setcookie($cookie_name, "", time() - 3600, "/", true, true);

    // Çıkış yaptıktan sonra yönlendirilecek sayfaya yönlendir
    header("Location: index.php"); // Örnek olarak index.php'yi kullanabilirsiniz
    exit();
} else {
    // Oturum zaten kapalıysa başka bir sayfaya yönlendir
    header("Location: index.php");
    exit();
}
?>
