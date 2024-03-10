<?php
require('server/database.php');
require('server/security.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF saldırısı algılandı.");
    }

    
$kullaniciAdi = isset($_POST['kullanici_adi']) ? trim($_POST['kullanici_adi']) : '';

if ($kullaniciAdi !== '') {
    if (isset($_POST['metin']) && !empty($_POST['metin'])) {
        $metin = isset($_POST['metin']) ? trim($_POST['metin']) : '';
    } elseif (isset($_FILES["dosyaSec"]) && $_FILES["dosyaSec"]["error"] == UPLOAD_ERR_OK) {
        $dosyaSec = $_FILES["dosyaSec"];
        $dosyaBoyutu = $dosyaSec["size"];



        if ($dosyaBoyutu > 10240 || pathinfo($dosyaSec["name"], PATHINFO_EXTENSION) !== "key") {
            echo "Hata: Dosya boyutu 10KB'dan büyük veya .key uzantılı değil!";
            exit();
        } else {
            
            if (isset($_POST['secenek']) && $_POST['secenek'] === 'dosya') {
                $dosyaIcerik = file_get_contents($dosyaSec["tmp_name"]);
                $metin = base64_decode($dosyaIcerik);
            }
        }
    } else {
        echo "Hata: Metin veya dosya seçeneği eksik!";
        exit();
    }

    try {
            // Kullanıcı adını veritabanında kontrol et
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param('s', $kullaniciAdi);
        $stmt->execute();
            $stmt->store_result(); // Sonuçları depolamak için

            // Kullanıcı bulunduysa işlemleri gerçekleştir
            if ($stmt->num_rows > 0) {
                $stmt->bind_result($userId);
                $stmt->fetch();

                $stmtt = $conn->prepare("SELECT recovery_key FROM salts WHERE user_id = ?");
                $stmtt->bind_param('s', $userId);
                $stmtt->execute();
                $stmtt->store_result();

                if ($stmtt->num_rows > 0) {
                    $stmtt->bind_result($save_recovery_key);
                    $stmtt->fetch();
                } else {
                    echo "Malesef bu hesap kurtarılamaz. Daha önceden oluşturulmuş kurtarma kodu bulunamadı.";
                    exit();
                }

                // Şifreleme işlemi (SHA512 + salt)
                $hashedInput1 = hash('sha512', $metin);
                $hashedInput = hash('sha512', $hashedInput1);

                // Veritabanındaki recovery_key ile karşılaştır
                if ($hashedInput === $save_recovery_key) {
                 echo "Doğru!";

    // 128 karakterli rastgele bir kod oluştur
                 $recoveryCode = bin2hex(random_bytes(64));

    // Session'a kodu ve ilişkilendirilecek hesabı kaydet
                 $_SESSION['recovery_code'] = $recoveryCode;
                 $_SESSION['user_account'] = $kullaniciAdi;

    // Yönlendirme işlemi

                 header("Location: sifre_yenile.php?code=$recoveryCode");




             } else {
                    //Hatalı şifre
                echo "Hatalı şifre";
            }
        } else {
            echo "Kullanıcı bulunamadı!";
        }
    } catch (PDOException $e) {
        echo "Hata: " . $e->getMessage();
    }
} else {
    echo "Hata: Kullanıcı adı eksik!";
}
}

// Veritabanı bağlantısını kapat
$conn->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kullanıcı Bilgileri</title>
</head>
<body>

    <form id="form" method="POST" enctype="multipart/form-data">
        <?php

        $csrf_token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $csrf_token;
        ?>
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <label>
          <input type="radio" name="secenek" value="metin" id="metinRadio"> Metin
      </label>
      <br>
      <label>
          <input type="radio" name="secenek" value="dosya" id="dosyaRadio"> Dosya
      </label>
      <br><br>

      <label for="kullanici_adi">Kullanıcı Adı:</label>
      <input type="text" id="kullanici_adi" name="kullanici_adi" required>
      <br><br>

      <div id="metinAlanlari" style="display: none;">
          <label for="metin">Metin:</label>
          <textarea id="metin" name="metin"></textarea>
          <br><br>
      </div>

      <div id="dosyaAlanlari" style="display: none;">
          <label for="dosyaSec">Dosya Seç:</label>
          <input type="file" id="dosyaSec" name="dosyaSec" accept=".key" >
          <small>En fazla 10KB boyutunda .key uzantılı dosya seçiniz.</small>
          <br><br>
      </div>

      <button type="submit">Gönder</button>
  </form>

  <script>
    const form = document.getElementById('form');
    const metinRadio = document.getElementById('metinRadio');
    const dosyaRadio = document.getElementById('dosyaRadio');
    const metinAlanlari = document.getElementById('metinAlanlari');
    const dosyaAlanlari = document.getElementById('dosyaAlanlari');
    
    metinRadio.addEventListener('change', function() {
      if (this.checked) {
        metinAlanlari.style.display = 'block';
        dosyaAlanlari.style.display = 'none';
    }
});

    dosyaRadio.addEventListener('change', function() {
      if (this.checked) {
        dosyaAlanlari.style.display = 'block';
        metinAlanlari.style.display = 'none';
    }
});

    form.addEventListener('submit', function(event) {
      const kullaniciAdi = document.getElementById('kullanici_adi').value;
      const metin = document.getElementById('metin').value;
      const dosyaSec = document.getElementById('dosyaSec');
      
      if (!kullaniciAdi || (dosyaRadio.checked && dosyaSec.files.length === 0)) {
        alert('Lütfen gerekli alanları doldurun.');
        event.preventDefault();
    } else if (dosyaRadio.checked) {
        const dosyaBoyutu = dosyaSec.files[0].size;
        if (dosyaBoyutu > 10240) {
          alert('Dosya boyutu 10KB\'dan büyük olamaz.');
          event.preventDefault();
      }
  }
});
</script>

</body>
</html>
