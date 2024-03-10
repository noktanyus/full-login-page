<?php
// Oturum kontrolü
require('server/database.php');
require('server/security.php');
// Kullanıcı adını al
$id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mesaj Paneli</title>
</head>
<body>
    <h1>Mesaj Paneli - Hoş Geldin, <?php echo $id; ?>!</h1>
    <p><a href="sifrekurtarma.php">Güvenlik kodunu oluşturmak için tıklayın</a></p>
    
    <!-- Buraya mesajları göstermek için gerekli kodları ekleyebilirsin -->
    
    <p><a href="cikis.php">Çıkış Yap</a></p>
</body>
</html>
