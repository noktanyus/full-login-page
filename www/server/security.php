<?php
session_set_cookie_params([
    'secure' => true,
    'httponly' => true,
]);
ini_set('session.use_only_cookies', 1);
session_start();

// Oturum açılmış mı kontrolü
if (isset($_SESSION['user_id'])) {
    // Oturum açılmış

    // Cookie süresi kontrolü
    $cookie_name = "session_id";
    $cookie_time = 100 * 3600; // 100 saat

    if (isset($_COOKIE[$cookie_name]) && time() - $_COOKIE[$cookie_name] > $cookie_time) {
        // Cookie süresi geçmiş, oturumu sonlandır, yeni oturum kimliği oluştur ve giris.php sayfasına yönlendir
        session_destroy();
        setcookie($cookie_name, "", time() - 3600, "/");
        $old_session = $_SESSION;
        session_regenerate_id(true);
        $_SESSION = array_merge($old_session, $_SESSION); // Yeni oturum kimliği oluştur
        header("Location: giris.php");
        exit();
            } else {
        // Cookie süresi geçmemiş

        // Sadece oturum ile ziyaretine izin verilen sayfalar
    $oturumluerisim = ["panel.php", "sifrekurtarma.php"];

// Panel sayfasında ise giris.php sayfasına yönlendir
    if (!in_array(basename($_SERVER['SCRIPT_NAME']), $oturumluerisim)) {
        header("Location: panel.php");
        exit();
    }

        // Cookie süresini 100 saat sonra güncelle
    setcookie($cookie_name, time(), time() + $cookie_time, "/", true, true);
}
} else {
    // Oturum açılmadan ziyarete izin verilen siteler
    $oturumsuzerisim = ["giris.php", "kayitol.php", "sifremiunuttum.php", "sifre_yenile.php"];

// Panel sayfasında ise giris.php sayfasına yönlendir
    if (!in_array(basename($_SERVER['SCRIPT_NAME']), $oturumsuzerisim)) {
        header("Location: giris.php");
        exit();
    }

}
?>