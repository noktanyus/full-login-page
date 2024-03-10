<?php

require('server/database.php');
require('server/security.php');

// Kullanıcıdan alınan bilgiler
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF saldırısı algılandı.");
    }

$username = htmlspecialchars($_POST["username"], ENT_QUOTES, 'UTF-8');
$password = htmlspecialchars($_POST["password"], ENT_QUOTES, 'UTF-8');


    // Rastgele 32 karakterli kod oluştur
    $randomCode = bin2hex(random_bytes(16));

    // Şifre ve kodu birleştir
    $combinedString = $password . $randomCode;

    // SHA256 ile hashle
    $hashedPassword = hash("sha256", $combinedString);

    // AES-256-CBC için anahtar oluştur
    $key = $key;//bin2hex(random_bytes(16)); 

    // AES-256-CBC ile kodu hashle
    $iv = bin2hex(openssl_random_pseudo_bytes(8)); // 16 byte 
    $encryptedCode = openssl_encrypt($randomCode, 'aes-256-cbc', $key, 0, $iv);

    // Veritabanına kullanıcıyı ekle ve kontrol et
    $checkQuery = "SELECT * FROM users WHERE username=?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $checkResult = $stmt->get_result();

    if ($checkResult->num_rows > 0) {
        echo "Bu kullanıcı adı zaten alınmış.";
    } else {
        $sql = "INSERT INTO users (username, password_hash) VALUES (?, ?)";
        $stmtt = $conn->prepare($sql);
        $stmtt->bind_param("ss", $username, $hashedPassword);

        if ($stmtt->execute()) {
            echo "Kullanıcı başarıyla kaydedildi.";

            // Kullanıcı ID'sini al
            $userIdQuery = "SELECT id FROM users WHERE username = ?";
            $stmttt = $conn->prepare($userIdQuery);
            $stmttt->bind_param("s", $username);
            $stmttt->execute();
            $userIdResult = $stmttt->get_result();

            if ($userIdResult->num_rows > 0) {
                $userId = $userIdResult->fetch_assoc()['id'];

                // Salts veritabanına kaydet
                $insertSaltQuery = "INSERT INTO salts (user_id, salt_value, iv) VALUES (?, ?, ?)";
                $stmtttt = $conn->prepare($insertSaltQuery);
                $stmtttt->bind_param("sss", $userId, $encryptedCode, $iv);
                $stmtttt->execute();

                if ($stmtttt->affected_rows > 0) {
                    echo "Salt başarıyla kaydedildi.";
                } else {
                    echo "Salt eklenirken bir hata oluştu.";
                }
            } else {
                echo "Kullanıcı ID'si alınamadı.";
            }
        } else {
            echo "Hata: Kullanıcı eklenirken bir sorun oluştu.";
        }
    }

    // Veritabanı bağlantısını kapat
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Formu</title>
</head>
<body>
    <h2>Kayıt Formu</h2>
    <form action="" method="post">
                <?php
            
            $csrf_token = bin2hex(random_bytes(32));
            $_SESSION['csrf_token'] = $csrf_token;
        ?>
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <label for="username">Kullanıcı Adı:</label>
        <input type="text" id="username" name="username" required><br>

        <label for="password">Şifre:</label>
        <input type="password" id="password" name="password" required><br>

        <input type="submit" value="Kayıt Ol">
    </form>
</body>
</html>
