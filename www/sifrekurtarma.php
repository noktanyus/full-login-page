<?php
require('server/database.php');
require('server/security.php');

// Txt dosyasının yolu
$txtDosyaYolu = 'C:/laragon/www/kelimelistesi.txt';

// Txt dosyasındaki kelimeleri oku
$kelimeler = file($txtDosyaYolu, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// Eğer dosya okunamazsa veya içeriği boşsa hata mesajı ver
if ($kelimeler === false || empty($kelimeler)) {
    die("Dosya okunamadı veya içeriği boş.");
}

// Eğer dosyadaki kelime sayısı 33'ten azsa, tüm kelimeleri kullan
if (count($kelimeler) <= 33) {
    $secilenKelimeler = $kelimeler;
} else {
    // Kelimeleri karıştır
    shuffle($kelimeler);

    // İlk 33 kelimeyi al
    $secilenKelimeler = array_slice($kelimeler, 0, 128);
}

// Oluşturulan kelimeleri ekrana yazdır
//echo implode($secilenKelimeler);

// Seçilen kelimeleri arka arkaya sıralayarak SHA512 ile şifrele
$plaintext = implode(', ', $secilenKelimeler);
echo $plaintext;

$hashed_password = hash('sha512', $plaintext);

// Session'dan user_id'yi çek
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Şifreleme işlemi
    $hashed_password = hash('sha512', $hashed_password);

    // Parametreize sorguları kullanarak SQL sorgusu oluştur
    $check_query = "SELECT user_id FROM salts WHERE user_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param('s', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // user_id zaten var, bu durumda UPDATE işlemi gerçekleştirin.
        $update_query = "UPDATE salts SET recovery_key = ? WHERE user_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param('ss', $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            echo "<br>Recovery key başarıyla güncellendi.";

            // Geçici bir önbellek dosyası oluştur
            $memoryStream = fopen('php://temp', 'r+');

            // Dosyaya veriyi yaz
            fwrite($memoryStream, base64_encode($plaintext));

            // Dosyanın başına dön
            rewind($memoryStream);

            // Veriyi oku
            $encryptedData = stream_get_contents($memoryStream);

            // İndirme butonunu göster
            echo '<br><a href="data://text/plain;base64,' . base64_encode($encryptedData) . '" download="kurtarma_anahtari_' . $user_id . '.key"><button>Kurtarma Anahtarını İndir</button></a>';

            // Önbellek dosyasını kapatma (kapatıldığında otomatik olarak silinir)
            fclose($memoryStream);
        } else {
            echo "<br>Hata: Recovery key güncellenirken bir sorun oluştu.";
        }
    } else {
        echo "Sen nasıl geldin buraya";
        die();
    }

    $stmt->close();
    $conn->close();
} else {
    echo "<br>Kullanıcı oturumu bulunamadı.";
}
?>
