<?php
require('server/database.php');
require('server/security.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF saldırısı algılandı.");
    }

    // Kullanıcı adı ve şifre
    $username = $_SESSION['user_account'];
    $password = htmlspecialchars($_POST["password"], ENT_QUOTES, 'UTF-8');

    // Rastgele 32 karakterli kod oluştur
    $randomCode = bin2hex(random_bytes(16));

    // SHA256 ile hashle
    $hashedPassword = hash("sha256", $password . $randomCode);

    // AES-256-CBC için anahtar oluştur
    $key = $key; // Anahtarı buraya ekleyin

    // AES-256-CBC ile kodu hashle
    $iv = bin2hex(random_bytes(8)); // 16 byte
    $encryptedCode = openssl_encrypt($randomCode, 'aes-256-cbc', $key, 0, $iv);

    // Veritabanına kullanıcıyı kontrol et
    $checkQuery = "SELECT * FROM users WHERE username=?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $checkResult = $stmt->get_result();

    if ($checkResult->num_rows > 0) {
        $row = $checkResult->fetch_assoc();
        $userId = $row['id'];

        // Kullanıcı var, şifre ve salt'ı güncelle
        $updateQuery = "UPDATE users SET password_hash=? WHERE username=?";
        $stmtUpdate = $conn->prepare($updateQuery);
        $stmtUpdate->bind_param("ss", $hashedPassword, $username);

        if ($stmtUpdate->execute()) {
            // Şifre başarıyla güncellendi
            echo "Şifre başarıyla güncellendi.";

            // Salt'ı güncelle
            $updateSaltQuery = "UPDATE salts SET salt_value=?, iv=? WHERE user_id=(SELECT id FROM users WHERE username=?)";
            $stmtSalt = $conn->prepare($updateSaltQuery);
            $stmtSalt->bind_param("sss", $encryptedCode, $iv, $username);
            $stmtSalt->execute();

            if ($stmtSalt->affected_rows > 0) {
                // Salt başarıyla güncellendi
                echo "Salt başarıyla güncellendi.";

                $cookie_name = "session_id";

                session_destroy();
                setcookie($cookie_name, "", time() - 3600, "/");
                session_regenerate_id(true);

                // Recovery kodunu sil
                $recoveryDeleteQuery = "UPDATE salts SET recovery_key = NULL WHERE user_id = ?";
                $stmtRecoveryDelete = $conn->prepare($recoveryDeleteQuery);
                $stmtRecoveryDelete->bind_param("i", $userId);
                $stmtRecoveryDelete->execute();

                if ($stmtRecoveryDelete->affected_rows > 0) {
                    echo "Recovery kodu silindi";
                } else {
                    echo "Recovery kodu silinirken hata oluştu.";
                }

                header("Location: giris.php");
            } else {
                echo "Salt güncellenirken bir hata oluştu.";
            }
        } else {
            echo "Şifre güncellenirken bir hata oluştu.";
        }
    } else {
        // Kullanıcı bulunamadı
        echo "Bu kullanıcı adı kayıtlı değil.";
    }

    // Veritabanı bağlantısını kapat
    $stmt->close();
    $stmtUpdate->close();
    $stmtSalt->close();
    $stmtRecoveryDelete->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Meta etiketleri buraya eklenecek -->
</head>
<body>
    <form method="post" onsubmit="return validatePassword()">
        <!-- CSRF koruma token'i -->
        <?php
        $csrf_token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $csrf_token;
        ?>
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

        <!-- Kullanıcı şifresi -->
        <label for="password">Yeni Şifre:</label>
        <input type="password" name="password" id="password" required>

        <!-- Şifreyi tekrar girme alanı -->
        <label for="confirm_password">Şifreyi Tekrar Girin:</label>
        <input type="password" name="confirm_password" id="confirm_password" required>

        <!-- Formu gönderme düğmesi -->
        <input type="submit" value="Şifreyi Güncelle">
    </form>
</body>
</html>
