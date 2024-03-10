<?php
require('server/database.php');
require('server/security.php');




if ($_SERVER['REQUEST_METHOD'] == 'POST') {



    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF saldırısı algılandı.");
    }

    $username = htmlspecialchars($_POST["username"], ENT_QUOTES, 'UTF-8');
    $password = htmlspecialchars($_POST["password"], ENT_QUOTES, 'UTF-8');


    // Veritabanından kullanıcıyı kontrol et
    $checkQuery = "SELECT * FROM users WHERE username=?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $checkResult = $stmt->get_result();

    if ($checkResult->num_rows > 0) {
        $userRow = $checkResult->fetch_assoc();
        $userId = $userRow['id'];
        $hashedPassword = $userRow['password_hash'];

        // Veritabanından kullanıcının salt bilgisini al
        $saltQuery = "SELECT * FROM salts WHERE user_id=?";
        $stmtt = $conn->prepare($saltQuery);
        $stmtt->bind_param("s", $userId);
        $stmtt->execute();
        $saltResult = $stmtt->get_result();

        if ($saltResult->num_rows > 0) {
            $saltRow = $saltResult->fetch_assoc();
            $encryptedCode = $saltRow['salt_value'];
            $iv = $saltRow['iv'];

            // Girilen şifreyi ve salt'ı birleştir
            $combinedString = $password . openssl_decrypt($encryptedCode, 'aes-256-cbc', $key, 0, $iv);

            // Girilen bilgilerle veritabanındaki hash'i karşılaştır
            $hashedPasswordCheck = hash("sha256", $combinedString);

            if ($hashedPasswordCheck === $hashedPassword) {

                    // Rastgele bir session ID oluştur
                $session_id = bin2hex(random_bytes(32));

    // Session ID'yi ve kullanıcı ID'sini session değişkenlerine kaydet
                $_COOKIE['session_id'] = $session_id;
                $_SESSION['user_id'] = $userId;

    // 100 saatlik ömürlü bir session cookie oluştur
                setcookie('session_id', $session_id, time() + 360000, '/', true, true, true);

    // 'users' tablosundaki 'cookie' sütununu güncelle, session ID'yi kaydet
                $updateCookieQuery = "UPDATE users SET cookie=? WHERE id=?";
                $stmttt = $conn->prepare($updateCookieQuery);
                $stmttt->bind_param("ss", $session_id, $userId);
                $stmttt->execute();

               // echo "Giriş başarıyla yapıldı.";
                header("Location: panel.php");
            } else {
                echo "Hatalı şifre.";
            }
        } else {
            echo "Salt bilgisi bulunamadı.";
        }
    } else {
        echo "Bu kullanıcı adı ile kayıtlı bir kullanıcı bulunamadı.";
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
    <title>Giriş Formu</title>
</head>
<body>
    <h2>Giriş Formu</h2>
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

        <input type="submit" value="Giriş Yap">
    </form>

    <form action="sifremiunuttum.php" method="get">
        <input type="submit" value="Şifremi Unuttum">
    </form>
    
</body>
</html>
