<?php
/**
 * PhpStorm ile oluşturulmuştur.
 * Yazar            : CELALKUTLUER
 * Test Eden        : CELALKUTLUER
 * Hata Ayıklayan   : CELALKUTLUER
 * Date: 09.06.2020
 * Time: 20:00
 */
include "baglantilar.php";
require_once "class.upload.php";
include "fonksiyonlar.php";
include('../smtp/PHPMailerAutoload.php');

if (g('islem') == 'giris') {
    $eposta = p('eposta');
    $sifre = p('sifre');
    $toplam = p('toplam');
    $dkodu = p('dkodu');
    if (empty($eposta)) {
        echo "<div class='alert alert-warning'>Lütfen E-posta adresinizi giriniz.</div>";
    } else if (empty($sifre)) {
        echo "<div class='alert alert-warning'>Lütfen Şifrenizi giriniz.</div>";
    } elseif (empty($dkodu)) {
        echo "<div class='alert alert-warning'>Lütfen Doğrulama kodunu giriniz.</div>";
    } elseif ($toplam != md5($dkodu)) {
        echo "<div class='alert alert-warning'>Doğrulama kodunuz hatalı.</div>";
    } else {
        if (isset($_POST['rememberme'])) {
            setcookie('eposta', $eposta, strtotime("+7 day"), '/');
            setcookie('sifre', $sifre, strtotime("+7 day"), '/');
        }
        else {
            setcookie('eposta', '_', time() - 1);
            setcookie('sifre', '_', time() - 1);
        }
        $veri = $db->prepare("SELECT kul_Id, kul_Ad, kul_Soyad, kul_Eposta, kul_Bakiye,kul_Eposta_Dogrulama, kul_Sifre,kul_Yetki,kul_Pasif_Durum,kul_Pasif_Tarih,kul_Pasif_Sure FROM kullanicilar WHERE kul_Eposta='" . $eposta . "' AND (kul_Sifre='" . md5($sifre) . "' OR kul_Sifre_yeni='" . $sifre . "')");
        $veri->execute(array());
        $v = $veri->fetchAll(PDO::FETCH_ASSOC);
        $say = $veri->rowCount();
        foreach ($v as $ykul_bilgileri) ;
        if ($say) {
            if ($ykul_bilgileri['kul_Eposta_Dogrulama'] == "1") {
                if ($ykul_bilgileri['kul_Yetki'] == '1' || $ykul_bilgileri['kul_Yetki'] == '0') {
                    if ($ykul_bilgileri['kul_Pasif_Durum'] == '0') {
                        $bir = date_format(date_modify(date_create((new \DateTime())->format('Y-m-d H:i:s')), "+1 hours"), "d-m-Y H:i:s");
                        /**/
                        $datem = date_create($ykul_bilgileri['kul_Pasif_Tarih']);
                        $sure = "+" . $ykul_bilgileri['kul_Pasif_Sure'] . " days";
                        date_modify($datem, $sure);
                        $iki = date_format($datem, "d-m-Y H:i:s");
                        if (strtotime($iki) > strtotime($bir)) {
                            echo "<div class='alert alert-danger'>Hesabınız Yönetici tarafından pasife alınmıştır. </div>";
                            echo "<div class='alert alert-danger'> " . $iki . " den önce giriş yapamazsınız.</div>";
                            echo "<div class='alert alert-danger'>Şu anki zaman :" . $bir . "</div>";
                        } else {
                            $kul_gun = $db->prepare("UPDATE kullanicilar SET kul_Pasif_Durum='1' WHERE kul_Id=?");
                            $kul_gunce = $kul_gun->execute(array($ykul_bilgileri['kul_Id']));
                            /**/
                            $ekl_log = $db->prepare("INSERT INTO log(log_kul_id, log_eylem, log_aciklama) VALUES ('" . $ykul_bilgileri['kul_Id'] . "','Pasif Süre Dolması','" . $ykul_bilgileri['kul_Id'] . " -Nolu kullanıcı " . $ykul_bilgileri['kul_Ad'] . " " . $ykul_bilgileri['kul_Soyad'] . ", pasif kaldığı sürenin dolması nedeniyle aktif duruma getirildi.')");
                            $eklem_log = $ekl_log->execute(array());
                        }
                    } else {
                        $_SESSION['isim'] = $ykul_bilgileri['kul_Ad'];
                        $_SESSION['soyisim'] = $ykul_bilgileri['kul_Soyad'];
                        $_SESSION['eposta'] = $ykul_bilgileri['kul_Eposta'];
                        $_SESSION['yetki'] = $ykul_bilgileri['kul_Yetki'];
                        $_SESSION['bakiye'] = $ykul_bilgileri['kul_Bakiye'];
                        $_SESSION['kul_id'] = $ykul_bilgileri['kul_Id'];
                        echo "<div class='alert alert-success'>Giriş Başarılı Lütfen Bekleyiniz</div><meta http-equiv='refresh' content='1; url=index.php'>";
                        $kul_gunce = $db->prepare("UPDATE kullanicilar SET kul_Son_Giris_Tar=CURRENT_TIMESTAMP WHERE kul_Id=?");
                        $kul_guncellemem = $kul_gunce->execute(array($ykul_bilgileri['kul_Id']));
                        $ekle_log = $db->prepare("INSERT INTO log(log_kul_id, log_eylem, log_aciklama) VALUES ('" . $ykul_bilgileri['kul_Id'] . "','Giriş','" . $ykul_bilgileri['kul_Id'] . " -Nolu kullanıcı " . $ykul_bilgileri['kul_Ad'] . " " . $ykul_bilgileri['kul_Soyad'] . ", " . $_SERVER['REMOTE_ADDR'] . " ip adresi üzerinden giriş yaptı.')");
                        $ekleme_log = $ekle_log->execute(array());
                        if ($ekleme_log) {
                           /* echo "<div class='alert alert-success'>Log Ekleme İşlemi Tamamlandı.</div>";*/
                        } else {
                            /*echo "<div class='alert alert-danger'>Log Kayıt İşlemi Başarısız.</div>";*/
                        }
                    }
                } else {
                    echo "<div class='alert alert-warning'>Giriş yetkiniz bulunmamaktadır.</div>";
                }
            } else {
                echo "<div class='alert alert-warning'>Üyelik Doğrulama işlemini Yapmadığınız Tepit Edildi. E-posta Adresinize Gelen Kodu Girerek Üyeliğinizi Aktif Edin</div><meta http-equiv='refresh' content='2; url=kayit-dogrulama.php'>";
            }

        } else {
            echo "<div class='alert alert-warning'>Böyle Bir Kullanıcı Bulunmamaktadır. Lütfen Kayit olun</div><meta http-equiv='refresh' content='1; url=kayit.php'>";
        }
    }
}
if (g('islem') == 'cikis') {
    session_unset();
    session_destroy();
    header("Location:../index.php");
    exit;
}
if (g('islem') == 'kayit') {
    $Ad = p('frmKayitAd');
    $Soyad = p('frmKayitSoyad');
    $Email = p('frmKayitEmail');
    $Sifre = p('frmKayitSifre');
    $Sifreconfirm = p('frmKayitSifreconfirm');
    $Dogum_tar = p('frmKayitDogum_tar');
    $CepTelNo = NumarayiFormatla(p('frmKayitCepTelNo'));
    $toplam = p('frmKayittoplam');
    $dkodu = p('frmKayitdkodu');
    $Sozlesme = p('frmKayitSozlesme');
    $yas = date_diff(date_create($Dogum_tar), date_create('today'))->y;
    if (empty($Ad)) {
        echo "<div class='alert alert-warning'>Lütfen Adınızı giriniz.</div>";
    } else if (empty($Soyad)) {
        echo "<div class='alert alert-warning'>Lütfen Soyadınızı giriniz.</div>";
    } else if (empty($Email)) {
        echo "<div class='alert alert-warning'>Lütfen E-posta adresinizi giriniz.</div>";
    } else if (empty($Sifre)) {
        echo "<div class='alert alert-warning'>Lütfen Şifrenizi giriniz.</div>";
    } else if (empty($Sifreconfirm)) {
        echo "<div class='alert alert-warning'>Lütfen Şifrenizi tekrar giriniz.</div>";
    } else if (empty($Dogum_tar)) {
        echo "<div class='alert alert-warning'>Lütfen Doğum Tarihinizi giriniz.</div>";
    } else if (empty($CepTelNo)) {
        echo "<div class='alert alert-warning'>Lütfen Cep Telefon Numaranızı giriniz.</div>";
    } elseif (empty($dkodu)) {
        echo "<div class='alert alert-warning'>Lütfen Doğrulama kodunu giriniz.</div>";
    } elseif ($toplam != md5($dkodu)) {
        echo "<div class='alert alert-warning'>Doğrulama kodunuz hatalı.</div>";
    } elseif (!filter_var($Email, FILTER_VALIDATE_EMAIL)) {
        echo "<div class='alert alert-warning'>Eposta adresi hatalı.</div>";
    } elseif ($yas <= 18) {
        echo "<div class='alert alert-warning'>18 Yaşından Küçüksünüz Bulaşmayın Bu İşlere...</div>";
    } else {
        if (kelime_kontrol($Ad) == 0 && kelime_kontrol($Soyad) == 0) {
            $veri1 = $db->prepare('SELECT kul_Eposta FROM kullanicilar WHERE kul_Eposta=?');
            $veri1->execute(array($Email));
            $v1 = $veri1->fetchAll(PDO::FETCH_ASSOC);
            $say1 = $veri1->rowCount();
            foreach ($v1 as $kul_kayit) ;
            if (!$say1) {
                $otp = rand(100000, 999999);
                $ekle = $db->prepare("INSERT INTO kullanicilar(kul_Ad, kul_Soyad, kul_Eposta,kul_Eposta_Dogrulama_Kod, kul_CepNo, kul_DogumTar, kul_Sifre) VALUES ('" . $Ad . "','" . $Soyad . "','" . $Email . "','" . $otp . "','" . $CepTelNo . "','" . $Dogum_tar . "','" . md5($Sifre) . "')");
                $ekleme = $ekle->execute(array());
                if ($ekleme) {
                    $html = "<strong>Üyeliğiniz oluşturulmuştur. Doğrulama Kodunuz : </strong>" . $otp;

                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = "smtp.gmail.com";
                    $mail->Port = 587;
                    $mail->SMTPSecure = "tls";
                    $mail->SMTPAuth = true;
                    $mail->Username = "borsayatirimfantaziligi@gmail.com";
                    $mail->Password = "Aa123456789.";
                    $mail->SetFrom($Email);
                    $mail->addAddress($Email);
                    $mail->IsHTML(true);
                    $mail->Subject = "Uyelik Dogrulama";
                    $mail->Body = $html;
                    $mail->SMTPOptions = array('ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => false
                    ));
                    if ($mail->send()) {
                        echo "<div class='alert alert-success'>E-posta adresinize doğrulama kodu gönderildi.</div>";
                    } else {
                        echo "<div class='alert alert-danger'>E-posta adresinize doğrulama kodu gönderildilemedi.</div>";
                    }
                    /**/
                    echo "<div class='alert alert-success'>Kayit işleminiz başarı ile gerçekleştirildi.</div><meta http-equiv='refresh' content='3; url=kayit-dogrulama.php'>";
                    $veri_log = $db->prepare('SELECT kul_id FROM kullanicilar WHERE kul_Eposta=?');
                    $veri_log->execute(array($Email));
                    $v_log = $veri_log->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($v_log as $kul_kayit_log) ;
                    /**/
                    $ekle_log = $db->prepare("INSERT INTO log(log_kul_id, log_eylem, log_aciklama) VALUES ('" . $kul_kayit_log['kul_id'] . "','Üyelik Kaydı','" . $kul_kayit_log['kul_id'] . " -Nolu kullanıcı " . $Ad . " " . $Soyad . ", " . $_SERVER['REMOTE_ADDR'] . " ip adresi üzerinden " . $Email . " e-posta adresi ile üyelik başvurusunda bulundu.')");
                    $ekleme_log = $ekle_log->execute(array());
                    if ($ekleme_log) {
                       /* echo "<div class='alert alert-success'>Log Ekleme İşlemi Tamamlandı.</div>";*/
                    } else {
                       /* echo "<div class='alert alert-danger'>Log Kayıt İşlemi Başarısız.</div>";*/
                    }
                } else {
                    echo "<div class='alert alert-danger'>Kayit işlemi sırasında bir hata meydana geldi</div>";
                }
            } else {
                echo "<div class='alert alert-info'>Eposta adresinize tanımlı üyelik mevcut. Lütfen Giriş yapınız.</div><meta http-equiv='refresh' content='1; url=giris.php'>";
            }
        } else {
            echo "<div class='alert alert-warning'>Yasaklı Kelimeler Bulundu.</div>";
        }
    }
}
if (g('islem') == 'kodD') {
    $toplam = p('toplam');
    $dkodu = p('dkodu');
    if ($toplam != md5($dkodu)) {
        echo "<div class='alert alert-warning'>Doğrulama kodunuz hatalı.</div>";
    } else {
        /**/
        if (empty(p('kod'))) {
            echo "<div class='alert alert-warning'>Lütfen Doğrulama Kodunuzu Giriniz.</div>";
        } elseif (!is_numeric(p('kod'))) {
            echo "<div class='alert alert-warning'>Lütfen Doğrulama Kodunuzu Sayısal Olarak Giriniz.</div>";
        } else {
            $veri_kod = $db->prepare('SELECT kul_Id,kul_eposta,kul_eposta_dogrulama_kod,`kul_Eposta_Dogrulama` FROM kullanicilar WHERE kul_eposta_dogrulama_kod=? and `kul_Eposta_Dogrulama`="0" limit 1');
            $veri_kod->execute(array(p('kod')));
            $v_kod = $veri_kod->fetchAll(PDO::FETCH_ASSOC);
            $say_kod = $veri_kod->rowCount();
            if ($say_kod > 0) {
                foreach ($v_kod as $kullanici_kod) ;
                $guncelle_kod = $db->prepare('UPDATE kullanicilar SET kul_eposta_dogrulama="1" WHERE kul_Id=?');
                $guncelleme_kod = $guncelle_kod->execute(array($kullanici_kod['kul_Id']));
                if ($guncelleme_kod) {
                    echo "<div class='alert alert-success'>E-mail adresiniz onaylanmıştır.</div><meta http-equiv='refresh' content='1; url=giris.php'>";
                } else {
                    echo "<div class='alert alert-danger'>E-mail doğrulama başarısız.</div>";
                }
            } else {
                echo "<div class='alert alert-danger'>Bu kod için doğrulanacak bir kullanıcı bulunamadı</div>";
            }
        }
    }

}/*kod doğrulama*/
if (g('islem') == 'kodDt') {
    $toplam = p('toplam');
    $dkodu = p('dkodu');
    if ($toplam != md5($dkodu)) {
        echo "<div class='alert alert-warning'>Doğrulama kodunuz hatalı.</div>";
    } else {
        $veri = $db->prepare('SELECT kul_Id,kul_Eposta,kul_Eposta_Dogrulama_Kod,TIME_TO_SEC(timeDIFF(CURRENT_TIMESTAMP() , `kul_kod_zaman`)) as saniye FROM kullanicilar WHERE kul_Eposta=? and kul_Eposta_Dogrulama="0"');
        $veri->execute(array(p('kodt')));
        $v = $veri->fetchAll(PDO::FETCH_ASSOC);
        $say = $veri->rowCount();
        foreach ($v as $kul_bilgileri) ;
        if ($say) {
            if (empty(p('kodt'))) {
                echo "<div class='alert alert-warning'>Lütfen Eposta Adresinizi Giriniz.</div>";
            } elseif (!filter_var(p('kodt'), FILTER_VALIDATE_EMAIL)) {
                echo "<div class='alert alert-warning'>Girilen Eposta Adresi Hatalı.</div>";
            } elseif ((300 - $kul_bilgileri['saniye']) > 0) {
                echo "<div class='alert alert-warning'>Kod Gönderimi 5 dakikada bir yapılmaktadır. Lütfen Bekleyiniz</div>";
                echo '<script type="text/javascript"> setTimeout(disableElement("btnKodt"), ' . (300 - $kul_bilgileri['saniye']) . '*1000);  </script>';
            } else {
                $html = "<strong>Üyeliğiniz oluşturulmuştur. Doğrulama Kodunuz : </strong>" . $kul_bilgileri['kul_Eposta_Dogrulama_Kod'];
                /**/
                $ekle_yeni_ = $db->prepare("UPDATE `kullanicilar` SET kul_kod_zaman=CURRENT_TIMESTAMP() WHERE `kul_Id`=?");
                $ekleme_yeni_ = $ekle_yeni_->execute(array($kul_bilgileri['kul_Id']));
                /**/
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = "smtp.gmail.com";
                $mail->Port = 587;
                $mail->SMTPSecure = "tls";
                $mail->SMTPAuth = true;
                $mail->Username = "borsayatirimfantaziligi@gmail.com";
                $mail->Password = "Aa123456789.";
                $mail->SetFrom(p('kodt'));
                $mail->addAddress(p('kodt'));
                $mail->IsHTML(true);
                $mail->Subject = "Uyelik Dogrulama";
                $mail->Body = $html;
                $mail->SMTPOptions = array('ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => false
                ));
                if ($mail->send()) {
                    echo "<div class='alert alert-success'>E-posta adresinize doğrulama kodu gönderildi.</div>";
                } else {
                    echo "<div class='alert alert-danger'>E-posta adresinize doğrulama kodu gönderildilemedi.</div>";
                }
            }
        } else {
            echo "<div class='alert alert-danger'>Böyle Bir Kullanıcı Yok veya Doğrulama İşlemi Tamamlanmış</div>";
        }
    }
}/*kod doğrulama tekrar kod gönderim*/
if (g('islem') == 'sif_u') {
    $toplam = p('toplam');
    $dkodu = p('dkodu');
    if ($toplam != md5($dkodu)) {
        echo "<div class='alert alert-warning'>Doğrulama kodunuz hatalı.</div>";
    } else {
        $veri = $db->prepare('SELECT kul_Id,kul_Eposta,TIME_TO_SEC(timeDIFF(CURRENT_TIMESTAMP() , `kul_kod_zaman`)) as saniye FROM kullanicilar WHERE kul_Eposta=?');
        $veri->execute(array(p('sif_u_eposta')));
        $v = $veri->fetchAll(PDO::FETCH_ASSOC);
        $say = $veri->rowCount();
        foreach ($v as $kul_bilgileri) ;
        if ($say) {
            if ((300 - $kul_bilgileri['saniye']) > 0) {
                echo "<div class='alert alert-warning'>Şifre Gönderimi 5 dakikada bir yapılmaktadır. Lütfen Bekleyiniz</div>";
            } else {
                $otp = rand(10000000, 99999999);
                /**/
                $ekle_yeni_Sif = $db->prepare("UPDATE `kullanicilar` SET `kul_Sifre_yeni`='" . $otp . "',kul_kod_zaman=CURRENT_TIMESTAMP() WHERE `kul_Id`=?");
                $ekleme_yeni_Sif = $ekle_yeni_Sif->execute(array($kul_bilgileri['kul_Id']));
                /**/
                $html = "<strong> Lütfen Şifrenizi Sitemize Girer Girmez Sıfırlayın . Yeni Şifreniz : </strong>" . $otp;

                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = "smtp.gmail.com";
                $mail->Port = 587;
                $mail->SMTPSecure = "tls";
                $mail->SMTPAuth = true;
                $mail->Username = "borsayatirimfantaziligi@gmail.com";
                $mail->Password = "Aa123456789.";
                $mail->SetFrom(p('sif_u_eposta'));
                $mail->addAddress(p('sif_u_eposta'));
                $mail->IsHTML(true);
                $mail->Subject = "Uyelik Dogrulama";
                $mail->Body = $html;
                $mail->SMTPOptions = array('ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => false
                ));
                if ($mail->send()) {
                    echo "<div class='alert alert-success'>E-posta adresinize doğrulama kodu gönderildi.</div>";
                } else {
                    echo "<div class='alert alert-danger'>E-posta adresinize doğrulama kodu gönderildilemedi.</div>";
                }
            }

        } else {
            echo "<div class='alert alert-warning'>Girilen E-posta adresi Hatalı</div>";
        }
    }
}/*şifremi unuttum alanı*/
if (g('islem') == 'iletisim') {
    $i_Ad = p('i_Ad');
    $i_Soyad = p('i_Soyad');
    $i_Email = p('i_Email');
    $i_Txt = p('i_Txt');
    $i_dkodu = p('i_dkodu');
    $i_toplam = p('i_toplam');

    if ($i_toplam != md5($i_dkodu)) {
        echo "<div class='alert alert-warning'>Doğrulama kodunuz hatalı.</div>";
    } else {
        /**/
        if (empty($i_Ad)) {
            echo "<div class='alert alert-warning'>Lütfen İsminizi Giriniz.</div>";
        } elseif (empty($i_Soyad)) {
            echo "<div class='alert alert-warning'>Lütfen Soyisminizi Giriniz..</div>";
        } elseif (empty($i_Email)) {
            echo "<div class='alert alert-warning'>Lütfen Eposta Adresinizi Giriniz..</div>";
        } elseif (empty($i_Txt)) {
            echo "<div class='alert alert-warning'>Lütfen Mesajınızı Giriniz..</div>";
        } elseif (!filter_var($i_Email, FILTER_VALIDATE_EMAIL)) {
            echo "<div class='alert alert-warning'>Lütfen Eposta Adresinizi Doğru Giriniz..</div>";
        } else {
            $veri = $db->prepare('SELECT kul_Id,kul_Eposta FROM kullanicilar WHERE kul_Eposta=?');
            $veri->execute(array($i_Email));
            $v = $veri->fetchAll(PDO::FETCH_ASSOC);
            $say = $veri->rowCount();
            foreach ($v as $kul) ;
            /**/
            $verimsj = $db->prepare('SELECT TIME_TO_SEC(timeDIFF(CURRENT_TIMESTAMP() , `msj_zaman`)) as saniye FROM `mesajlar` WHERE `msj_eposta`=? ORDER BY `msj_zaman` DESC LIMIT 1');
            $verimsj->execute(array($i_Email));
            $vmsj = $verimsj->fetchAll(PDO::FETCH_ASSOC);
            $saymsj = $verimsj->rowCount();
            foreach ($vmsj as $msj) ;
            /**/
            if ($saymsj && (300 - $msj['saniye']) > 0) {
                echo "<div class='alert alert-warning'>Bir kişi enfazla 5 dk da bir mesaj gönderebilir. Lütfen bekleyiniz.</div>";
            } else {
                if ($say) {
                    $ekle = $db->prepare("INSERT INTO mesajlar (msj_ad , msj_soyad , msj_eposta , msj_text , msj_kul_id) VALUES ('" . $i_Ad . "','" . $i_Soyad . "','" . $i_Email . "','" . $i_Txt . "','" . $kul['kul_Id'] . "')");
                    $ekleme = $ekle->execute(array());
                    if ($ekleme) {
                        echo "<div class='alert alert-success'>Mesajınız Sistem Yöneticilerimize gönderildi. Vakit Ayırdığınız İçin Teşekkür Ederiz.</div>";
                    } else {
                        echo "<div class='alert alert-danger'>Mesajınız gönderilemedi</div>";
                    }
                } else {
                    $ekle = $db->prepare("INSERT INTO mesajlar (msj_ad , msj_soyad , msj_eposta , msj_text ) VALUES ('" . $i_Ad . "','" . $i_Soyad . "','" . $i_Email . "','" . $i_Txt . "')");
                    $ekleme = $ekle->execute(array());
                    if ($ekleme) {
                        echo "<div class='alert alert-success'>Mesajınız Sistem Yöneticilerimize gönderildi. Vakit Ayırdığınız İçin Teşekkür Ederiz.</div>";
                    } else {
                        echo "<div class='alert alert-danger'>Mesajınız gönderilemedi</div>";
                    }
                }
            }

        }
    }

}
if (g('islem') == 'profil_resim_kayit') {
    if ($_FILES['file']['name'] != "") {
        $name = $_FILES['file']['name'];
        $yol = '../img';
        $rn = resimadi();
        $uzanti = uzanti($name);
        $vtyol = "img/$rn.$uzanti";
        if ($_FILES['file']['size'] > 1024 * 1024) {
            echo "<div class='alert alert-warning'>Maximum resim boyutu 1 mb dan az olmalıdır.</div>";
        } else {
            $resimyükleme = resimyukle('file', $rn, $yol);
            if ($resimyükleme) {
                $veri_resim = $db->prepare('SELECT kul_Resim FROM kullanicilar WHERE kul_Id=?');
                $veri_resim->execute(array($_SESSION['kul_id']));
                $v_resim = $veri_resim->fetchAll(PDO::FETCH_ASSOC);
                foreach ($v_resim as $kul_resim) ;
                $eskiresim = "../" . $kul_resim['kul_Resim'];
                $ekle_res = $db->prepare("UPDATE `kullanicilar` SET `kul_Resim`='" . $vtyol . "' WHERE `kul_Id`=?");
                $ekleme_res = $ekle_res->execute(array($_SESSION['kul_id']));
                if ($ekleme_res) {
                    echo "<div class='alert alert-success'>Resim Güncelleme gerçekleştirildi.</div><meta http-equiv='refresh' content='1; url=profil.php'>";
                    $ekle_log = $db->prepare("INSERT INTO log(log_kul_id, log_eylem, log_aciklama) VALUES ('" . $_SESSION['kul_id'] . "','Profil Resim Değiştirme','" . $_SESSION['kul_id'] . " -Nolu kullanıcı " . $_SESSION['isim'] . " " . $_SESSION['soyisim'] . ", resmini değiştirdi.')");
                    $ekleme_log = $ekle_log->execute(array());
                    if ($ekleme_log) {
                      /*  echo "<div class='alert alert-success'>Log Ekleme İşlemi Tamamlandı.</div>";*/
                    } else {
                        /*echo "<div class='alert alert-danger'>Log Kayıt İşlemi Başarısız.</div>";*/
                    }
                } else {
                    echo "<div class='alert alert-danger'>Resim Güncelleme işlemi sırasında bir hata meydana geldi</div>";
                }
                if ($kul_resim['kul_Resim'] != 'img/logged-user.jpg') {
                    if ($ekleme_res) {
                        unlink($eskiresim);
                    }
                }
            } else {
                echo "<div class='alert alert-warning'>Resim Yüklenirken Bir Hata Oluştu.</div>";
            }
        }
    } else {
        echo "<div class='alert alert-warning'>Bir Profil Resmi Seçiniz.</div>";
    }
}
if (g('islem') == 'lig_olustur') {
    $baslik = p('lig_olustur_baslık');
    $duyuru = p('lig_olustur_duyuru');
    if (empty($baslik)) {
        echo "<div class='alert alert-warning'>Lütfen Lig Başlığı Yazın.</div>";
    } else if (empty($duyuru)) {
        echo "<div class='alert alert-warning'>Lütfen Lig Duyurusu Yazın.</div>";
    } else {

        $veri = $db->prepare('SELECT kul_lig_id,kul_Ad,kul_Soyad FROM kullanicilar WHERE kul_Id=?');
        $veri->execute(array($_SESSION['kul_id']));
        $v = $veri->fetchAll(PDO::FETCH_ASSOC);
        $say = $veri->rowCount();
        foreach ($v as $ligler) ;
        $veri = $db->prepare('SELECT lig_baslik FROM ligler WHERE lig_baslik=?');
        $veri->execute(array($baslik));
        $v = $veri->fetchAll(PDO::FETCH_ASSOC);
        $say = $veri->rowCount();
        foreach ($v as $ligler) ;
        if (!$say) {
            if (kelime_kontrol($baslik) == 0 || kelime_kontrol($duyuru) == 0) {
                $ekle = $db->prepare("INSERT INTO ligler( lig_baslik, lig_duyuru, lig_bos_uyelik, lig_yonetici_id) VALUES ('" . $baslik . "','" . $duyuru . "',9,'" . $_SESSION['kul_id'] . "')");
                $ekleme = $ekle->execute(array());
                $veri = $db->prepare('SELECT lig_id FROM ligler WHERE lig_baslik=?');
                $veri->execute(array($baslik));
                $v = $veri->fetchAll(PDO::FETCH_ASSOC);
                foreach ($v as $ligle) ;
                $kul_gunce = $db->prepare("UPDATE kullanicilar SET kul_lig_id='" . $ligle['lig_id'] . "' WHERE kul_Id=?");
                $kul_guncell = $kul_gunce->execute(array($_SESSION['kul_id']));
                if ($ekleme || $kul_guncell) {
                    echo "<div class='alert alert-success'>Lig Kayıt İşleminiz Gerçekleşti.</div><meta http-equiv='refresh' content='1; url=ligler.php'>";
                    $ekle_log = $db->prepare("INSERT INTO log(log_kul_id, log_eylem, log_aciklama) VALUES ('" . $_SESSION['kul_id'] . "','Lig Oluşturma','" . $_SESSION['kul_id'] . " -Nolu kullanıcı " . $ligler['kul_Ad'] . " " . $ligler['kul_Soyad'] . " " . $ligle['lig_id'] . " nolu " . $baslik . " ligini oluşturdu.')");
                    $ekleme_log = $ekle_log->execute(array());
                    if ($ekleme_log) {
                        /*echo "<div class='alert alert-success'>Log Ekleme İşlemi Tamamlandı.</div>";*/
                    } else {
                       /* echo "<div class='alert alert-danger'>Log Kayıt İşlemi Başarısız.</div>";*/
                    }
                } else {
                    echo "<div class='alert alert-danger'>Lig Kayıt İşlemi Başarısız oldu</div>";
                }
            } else {
                echo "<div class='alert alert-danger'>Lütfen Başka Lig başlığı veya Lig duyurusu Deneyiniz. Yasaklı kelimeler Bulunmuştur</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Girilen Lig Başlığı Mevcut. Başka Bir Lig Başlığı Deneyin.</div>";
        }
    }
}
if (g('islem') == 'lig_katil') {
    $baslik = p('lig_baslik');
    $veri_log = $db->prepare('SELECT kul_lig_id,kul_Ad,kul_Soyad FROM kullanicilar WHERE kul_Id=?');
    $veri_log->execute(array($_SESSION['kul_id']));
    $v_log = $veri_log->fetchAll(PDO::FETCH_ASSOC);
    foreach ($v_log as $ligler) ;
    $veri = $db->prepare('SELECT lig_id,lig_bos_uyelik FROM ligler WHERE lig_baslik=?');
    $veri->execute(array($baslik));
    $v = $veri->fetchAll(PDO::FETCH_ASSOC);
    foreach ($v as $ligle) ;
    if ($ligle['lig_bos_uyelik'] > 0) {
        $kul_gunce = $db->prepare("UPDATE kullanicilar SET kul_lig_id='" . $ligle['lig_id'] . "' WHERE kul_Id=?");
        $kul_guncellem = $kul_gunce->execute(array($_SESSION['kul_id']));
        $lig_gunce = $db->prepare("UPDATE ligler SET lig_bos_uyelik='" . ($ligle['lig_bos_uyelik'] - 1) . "' WHERE lig_Id=?");
        $lig_guncellemem = $lig_gunce->execute(array($ligle['lig_id']));
        if ($kul_guncellem && $lig_guncellemem) {
            echo "<div class='alert alert-success'>Seçilen Lige Kayıt İşleminiz Gerçekleşti.</div><meta http-equiv='refresh' content='1; url=ligler.php'>";
            $ekle_log = $db->prepare("INSERT INTO log(log_kul_id, log_eylem, log_aciklama) VALUES ('" . $_SESSION['kul_id'] . "','Lig Giriş','" . $_SESSION['kul_id'] . " -Nolu kullanıcı " . $ligler['kul_Ad'] . " " . $ligler['kul_Soyad'] . " " . $ligle['lig_id'] . " nolu " . $baslik . " ligine katıldı')");
            $ekleme_log = $ekle_log->execute(array());
            if ($ekleme_log) {
               /* echo "<div class='alert alert-success'>Log Ekleme İşlemi Tamamlandı.</div>";*/
            } else {
               /* echo "<div class='alert alert-danger'>Log Kayıt İşlemi Başarısız.</div>";*/
            }
        } else {
            echo "<div class='alert alert-success'>ok</div>";
        }
    } else {
        echo "<div class='alert alert-success'>Seçilen Ligde Boş Üyelik Mevcut Değil</div>";
    }
}
if (g('islem') == 'lig_yon_devret') {
    $kul_id = p('kul_id');
    $kul_lig_id = p('kul_lig_id');

    /**/
    $verim = $db->prepare('SELECT lig_id FROM ligler WHERE lig_id=?');
    $verim->execute(array($kul_lig_id));
    $vm = $verim->fetchAll(PDO::FETCH_ASSOC);
    $say_m = $verim->rowCount();
    foreach ($vm as $liglerim) ;
    /**/
    if ($say_m) {
        $lig_gunce = $db->prepare("UPDATE ligler SET lig_yonetici_id='" . $kul_id . "' WHERE lig_Id=?");
        $lig_guncellemem = $lig_gunce->execute(array($kul_lig_id));
        if ($lig_guncellemem) {
            echo "<div class='alert alert-success'>Lig devri İşleminiz Gerçekleşti.</div><meta http-equiv='refresh' content='1; url=ligim.php'>";
        } else {
            echo "<div class='alert alert-danger'>Lig devri İşleminiz Başarısız.</div>";
        }
        $ekle_log_Ad = $db->prepare("INSERT INTO log(log_kul_id, log_eylem, log_aciklama) VALUES ('" . $_SESSION['kul_id'] . "','Lig Yöneticilik Devri','" . $_SESSION['kul_id'] . " -Nolu kullanıcı " . $_SESSION['isim'] . " " . $_SESSION['soyisim'] . ", lig yöneticiliğini " . $kul_id . " id li üyeye devretti')");
        $ekleme_log_Ad = $ekle_log_Ad->execute(array());
        if ($ekleme_log_Ad) {
          /*  echo "<div class='alert alert-success'>Log Ekleme İşlemi Tamamlandı.</div>";*/
        } else {
          /*  echo "<div class='alert alert-danger'>Log Kayıt İşlemi Başarısız.</div>";*/
        }
    } else {
        echo "<div class='alert alert-warning'>Bu liğin yöneticisi değilsiniz</div>";
    }
}
if (g('islem') == 'lig_uye_at') {
    $kul_id = p('kul_id');
    $kul_lig_id = p('kul_lig_id');

    /**/
    $verim = $db->prepare('SELECT lig_baslik,lig_bos_uyelik,lig_yonetici_id FROM ligler WHERE lig_Id=?');
    $verim->execute(array($kul_lig_id));
    $vm = $verim->fetchAll(PDO::FETCH_ASSOC);
    foreach ($vm as $liglerim) ;
    /**/
    $lig_guncem = $db->prepare("UPDATE ligler SET lig_bos_uyelik='" . ($liglerim['lig_bos_uyelik'] + 1) . "' WHERE lig_Id=?");
    $lig_guncelleme = $lig_guncem->execute(array($kul_lig_id));
    $kul_gunce = $db->prepare("UPDATE kullanicilar SET kul_lig_id=null WHERE kul_Id=?");
    $kul_guncellemem = $kul_gunce->execute(array($kul_id));
    /**/
    if ($kul_guncellemem) {
        echo "<div class='alert alert-success'>Ligden çıkarma İşleminiz Gerçekleşti.</div><meta http-equiv='refresh' content='1; url=ligim.php'>";
    } else {
        echo "<div class='alert alert-danger'>Ligden çıkarma İşleminiz Başarısız.</div>";
    }
    $ekle_log_Ad = $db->prepare("INSERT INTO log(log_kul_id, log_eylem, log_aciklama) VALUES ('" . $_SESSION['kul_id'] . "','Ligden atma','" . $_SESSION['kul_id'] . " -Nolu kullanıcı " . $_SESSION['isim'] . " " . $_SESSION['soyisim'] . ", " . $liglerim['lig_yonetici_id'] . " id li yönetici tarafından " . $kul_id . " id li üye " . $liglerim['lig_baslik'] . " liginden çıkarıldı.')");
    $ekleme_log_Ad = $ekle_log_Ad->execute(array());
    if ($ekleme_log_Ad) {
        /*echo "<div class='alert alert-success'>Log Ekleme İşlemi Tamamlandı.</div>";*/
    } else {
       /* echo "<div class='alert alert-danger'>Log Kayıt İşlemi Başarısız.</div>";*/
    }

}
if (g('islem') == 'lig_ayril') {
    $veri = $db->prepare('SELECT kul_lig_id,kul_Ad,kul_Soyad FROM kullanicilar WHERE kul_Id=?');
    $veri->execute(array($_SESSION['kul_id']));
    $v = $veri->fetchAll(PDO::FETCH_ASSOC);
    foreach ($v as $kulla) ;
    /**/
    $verim = $db->prepare('SELECT lig_baslik,lig_bos_uyelik,lig_yonetici_id FROM ligler WHERE lig_Id=?');
    $verim->execute(array($kulla['kul_lig_id']));
    $vm = $verim->fetchAll(PDO::FETCH_ASSOC);
    foreach ($vm as $liglerim) ;
    /**/
    $lig_guncem = $db->prepare("UPDATE ligler SET lig_bos_uyelik='" . ($liglerim['lig_bos_uyelik'] + 1) . "' WHERE lig_Id=?");
    $lig_guncelleme = $lig_guncem->execute(array($kulla['kul_lig_id']));
    $kul_gunce = $db->prepare("UPDATE kullanicilar SET kul_lig_id=null WHERE kul_Id=?");
    $kul_guncellemem = $kul_gunce->execute(array($_SESSION['kul_id']));
    /**/
    $veri_sonmu = $db->prepare('SELECT `kul_Id` FROM `kullanicilar` WHERE `kul_lig_id`=? order by `kul_Uyelik_Tarih` ASC LIMIT 1');
    $veri_sonmu->execute(array($kulla['kul_lig_id']));
    $v_sonmu = $veri_sonmu->fetchAll(PDO::FETCH_ASSOC);
    $say_sonmu = $veri_sonmu->rowCount();
    foreach ($v_sonmu as $kulla_sonmu) ;
    if ($say_sonmu && $liglerim['lig_yonetici_id'] == $_SESSION['kul_id']) {
        $lig_gunce = $db->prepare("UPDATE ligler SET lig_yonetici_id='" . $kulla_sonmu['kul_Id'] . "' WHERE lig_Id=?");
        $lig_guncellemem = $lig_gunce->execute(array($kulla['kul_lig_id']));
    } elseif (!$say_sonmu) {
        $lig_gunce = $db->prepare("DELETE FROM `ligler` WHERE `lig_id`=?");
        $lig_guncellemem = $lig_gunce->execute(array($kulla['kul_lig_id']));
        echo "<div class='alert alert-success'>Liginizin son elemanı olduğunuz ve ayrıldığınızdan liginiz silindi.</div><meta http-equiv='refresh' content='2; url=ligler.php'>";
        $ekle_log_lig_sil = $db->prepare("INSERT INTO log(log_kul_id, log_eylem, log_aciklama) VALUES ('" . $_SESSION['kul_id'] . "','Lig Silme','" . $_SESSION['kul_id'] . " -Nolu kullanıcı " . $kulla['kul_Ad'] . " " . $kulla['kul_Soyad'] . " " . $kulla['kul_lig_id'] . " nolu " . $liglerim['lig_baslik'] . " liginden ayrıldı. Ligin son üyesi olduğundan lig silindi')");
        $ekleme_log_lig_sil = $ekle_log_lig_sil->execute(array());
        if ($ekleme_log_lig_sil) {
            /*echo "<div class='alert alert-success'>Log Ekleme İşlemi Tamamlandı.</div>";*/
        } else {
           /* echo "<div class='alert alert-danger'>Log Kayıt İşlemi Başarısız.</div>";*/
        }
    }
    if ($kul_guncellemem && $lig_guncelleme) {
        echo "<div class='alert alert-success'>Ligden Ayrılma İşleminiz Gerçekleşti.</div><meta http-equiv='refresh' content='1; url=ligler.php'>";
        $ekle_log = $db->prepare("INSERT INTO log(log_kul_id, log_eylem, log_aciklama) VALUES ('" . $_SESSION['kul_id'] . "','Lig Çıkış','" . $_SESSION['kul_id'] . " -Nolu kullanıcı " . $kulla['kul_Ad'] . " " . $kulla['kul_Soyad'] . " " . $kulla['kul_lig_id'] . " nolu " . $liglerim['lig_baslik'] . " liginden ayrıldı')");
        $ekleme_log = $ekle_log->execute(array());
        if ($ekleme_log) {
            /*echo "<div class='alert alert-success'>Log Ekleme İşlemi Tamamlandı.</div>";*/
        } else {
           /* echo "<div class='alert alert-danger'>Log Kayıt İşlemi Başarısız.</div>";*/
        }
    } else {
        echo "<div class='alert alert-success'>Lig Ayrılma İşleminiz Başarısız oldu</div>";
    }
}
if (g('islem') == 'pasife_al') {
    $id = p('id');
    $veri_logum = $db->prepare('SELECT log_kul_id FROM log WHERE log_eylem="Pasife Alma" AND log_kul_id=?');
    $veri_logum->execute(array($id));
    $say_logum = $veri_logum->rowCount();
    $kul_gunce = $db->prepare("UPDATE kullanicilar SET kul_Pasif_Durum='0',kul_Pasif_Tarih=CURRENT_TIMESTAMP ,kul_Pasif_Sure='" . ($say_logum + 1) . "' WHERE kul_Id=?");
    $kul_guncellemem = $kul_gunce->execute(array($id));
    $veri_log = $db->prepare('SELECT kul_lig_id,kul_Ad,kul_Soyad,kul_Pasif_Tarih,kul_Pasif_Sure FROM kullanicilar WHERE kul_Id=?');
    $veri_log->execute(array($id));
    $v_log = $veri_log->fetchAll(PDO::FETCH_ASSOC);
    foreach ($v_log as $kullanicilar) ;
    if ($kul_guncellemem) {
        echo "<div class='alert alert-success'>Kullanıcı Pasife Alma İşleminiz Gerçekleşti.</div><meta http-equiv='refresh' content='1; url=kullanicilar.php'>";
        $ekle_log = $db->prepare("INSERT INTO log(log_kul_id, log_eylem, log_aciklama) VALUES ('" . $id . "','Pasife Alma','" . $_SESSION['kul_id'] . " -Nolu Yönetici ," . $id . " nolu Kullanıcı olan " . $kullanicilar['kul_Ad'] . "" . $kullanicilar['kul_Soyad'] . " i" . $kullanicilar['kul_Pasif_Tarih'] . " de " . $kullanicilar['kul_Pasif_Sure'] . " günlüğüne pasife aldı')");
        $ekleme_log = $ekle_log->execute(array());
        if ($ekleme_log) {
            /*echo "<div class='alert alert-success'>Log Ekleme İşlemi Tamamlandı.</div>";*/
        } else {
           /* echo "<div class='alert alert-danger'>Log Kayıt İşlemi Başarısız.</div>";*/
        }
    } else {
        echo "<div class='alert alert-success'>Kullanıcı Pasife Alma İşleminiz Başarısız oldu</div>";
    }
}
if (g('islem') == 'yetki_ver') {
    $id = p('id');
    /**/
    $veri_k = $db->prepare('SELECT kul_Yetki FROM `kullanicilar` WHERE `kul_Id`=?');
    $veri_k->execute(array($id));
    $v_k = $veri_k->fetchAll(PDO::FETCH_ASSOC);
    foreach ($v_k as $k) ;
    /**/
    if ($k['kul_Yetki'] == 1) {
        $kul_gunce = $db->prepare("UPDATE kullanicilar SET kul_Yetki='0' WHERE kul_Id=?");
        $kul_guncellemem = $kul_gunce->execute(array($id));

        if ($kul_guncellemem) {
            echo "<div class='alert alert-success'>Kullanıcı Yetki Alma İşleminiz Gerçekleşti.</div><meta http-equiv='refresh' content='1; url=kullanicilar.php'>";
            $ekle_log = $db->prepare("INSERT INTO log(log_kul_id, log_eylem, log_aciklama) VALUES ('" . $id . "','Yönetici Yetkisi Alma','" . $_SESSION['kul_id'] . " -Nolu Yönetici ," . $id . " id li kullanıcının yönetici yetkisini aldı.')");
            $ekleme_log = $ekle_log->execute(array());
            if ($ekleme_log) {
               /* echo "<div class='alert alert-success'>Log Ekleme İşlemi Tamamlandı.</div>";*/
            } else {
               /* echo "<div class='alert alert-danger'>Log Kayıt İşlemi Başarısız.</div>";*/
            }
        } else {
            echo "<div class='alert alert-success'>Kullanıcı Yetki Alma İşleminiz Başarısız oldu</div>";
        }
    } else {
        $kul_gunce = $db->prepare("UPDATE kullanicilar SET kul_Yetki='1' WHERE kul_Id=?");
        $kul_guncellemem = $kul_gunce->execute(array($id));

        if ($kul_guncellemem) {
            echo "<div class='alert alert-success'>Kullanıcı Yetki Verme İşleminiz Gerçekleşti.</div><meta http-equiv='refresh' content='1; url=kullanicilar.php'>";
            $ekle_log = $db->prepare("INSERT INTO log(log_kul_id, log_eylem, log_aciklama) VALUES ('" . $id . "','Yönetici Yetkisi Verme','" . $_SESSION['kul_id'] . " -Nolu Yönetici ," . $id . " id li kullanıcıya yönetici yetkisi verdi.')");
            $ekleme_log = $ekle_log->execute(array());
            if ($ekleme_log) {
              /*  echo "<div class='alert alert-success'>Log Ekleme İşlemi Tamamlandı.</div>";*/
            } else {
              /*  echo "<div class='alert alert-danger'>Log Kayıt İşlemi Başarısız.</div>";*/
            }
        } else {
            echo "<div class='alert alert-success'>Kullanıcı Yetki Verme İşleminiz Başarısız oldu</div>";
        }
    }

}
if (g('islem') == 'mesaj_okundu') {
    $id = p('id');
    /**/
    $msj_gunce = $db->prepare("UPDATE  mesajlar  SET  msj_okundumu ='1', msj_okuyan_id =? WHERE  msj_id =?");
    $msj_guncellemem = $msj_gunce->execute(array($_SESSION['kul_id'], $id));
    if ($msj_guncellemem) {
        echo "<div class='alert alert-success'>Mesaj okuma İşleminiz Gerçekleşti.</div><meta http-equiv='refresh' content='1; url=mesajlar.php'>";

    } else {
        echo "<div class='alert alert-success'>Mesaj okuma İşleminiz Başarısız oldu</div>";
    }
}
if (g('islem') == 'mesaj_cevap') {
    $eposta = p('eposta');
    $mesaj_text = p('mesaj_text');
    /**/

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = "smtp.gmail.com";
    $mail->Port = 587;
    $mail->charSet = "UTF-8";
    $mail->SMTPSecure = "tls";
    $mail->SMTPAuth = true;
    $mail->Username = "borsayatirimfantaziligi@gmail.com";
    $mail->Password = "Aa123456789.";
    $mail->SetFrom($eposta);
    $mail->addAddress($eposta);
    $mail->IsHTML(true);
    $mail->Subject = "Iletisim Sayfamiza Yazmis oldugunuz mesajiniz hakkinda.";
    $mail->Body = $mesaj_text;
    $mail->SMTPOptions = array('ssl' => array(
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => false
    ));
    if ($mail->send()) {
        echo "<div class='alert alert-success'>E-posta gönderildi.</div>";
    } else {
        echo "<div class='alert alert-danger'>E-posta gönderilemedi.</div>";
    }

}
if (g('islem') == 'profil_bilgi_kaydet') {
    $profilAd = p('profilAd');
    $profilSoyad = p('profilSoyad');
    $profilEposta = p('profilEposta');
    $profilCepNo = NumarayiFormatla(p('profilCepNo'));
    $profilDogumTar = p('profilDogumTar');
    $id = p('kul_id');
    if (empty($profilAd)) {
        echo "<div class='alert alert-warning'>Lütfen Adınızı giriniz.</div>";
    } elseif (empty($profilSoyad)) {
        echo "<div class='alert alert-warning'>Lütfen Soyadınızı giriniz.</div>";
    } elseif (empty($profilEposta)) {
        echo "<div class='alert alert-warning'>Lütfen E-posta Adresinizi giriniz.</div>";
    } elseif (empty($profilCepNo)) {
        echo "<div class='alert alert-warning'>Lütfen Cep Numaranızı giriniz.</div>";
    } elseif (empty($profilDogumTar)) {
        echo "<div class='alert alert-warning'>Lütfen Doğum Tarihinizi giriniz.</div>";
    } elseif (!filter_var($profilEposta, FILTER_VALIDATE_EMAIL)) {
        echo "<div class='alert alert-warning'>Geçerli bir E-posta Adresi giriniz.</div>";
    } else {
        $veri_profil = $db->prepare('SELECT `kul_Ad`,`kul_Soyad`,`kul_Eposta`,`kul_CepNo`,`kul_DogumTar` FROM `kullanicilar` WHERE `kul_Id`=?');
        $veri_profil->execute(array($_SESSION['kul_id']));
        $v_profil = $veri_profil->fetchAll(PDO::FETCH_ASSOC);
        foreach ($v_profil as $profil) ;
        if ($profil['kul_Ad'] != $profilAd) {
            $kul_gunce_Ad = $db->prepare("UPDATE kullanicilar SET kul_Ad='" . $profilAd . "' WHERE kul_Id=?");
            $kul_guncellem_Ad = $kul_gunce_Ad->execute(array($_SESSION['kul_id']));
            if ($kul_guncellem_Ad) {
                echo "<div class='alert alert-success'>İsim Değişikliği İşleminiz Gerçekleşti.</div><meta http-equiv='refresh' content='1; url=profil.php'>";
            } else {
                echo "<div class='alert alert-danger'>İsim Değişikliği İşleminiz Başarısız.</div>";
            }
            $ekle_log_Ad = $db->prepare("INSERT INTO log(log_kul_id, log_eylem, log_aciklama) VALUES ('" . $_SESSION['kul_id'] . "','Profil Bilgi Güncelleme','" . $_SESSION['kul_id'] . " -Nolu kullanıcı " . $profil['kul_Ad'] . " " . $profil['kul_Soyad'] . ", ismini " . $profilAd . " olarak değiştirdi')");
            $ekleme_log_Ad = $ekle_log_Ad->execute(array());
            if ($ekleme_log_Ad) {
                /*echo "<div class='alert alert-success'>Log Ekleme İşlemi Tamamlandı.</div>";*/
            } else {
                /*echo "<div class='alert alert-danger'>Log Kayıt İşlemi Başarısız.</div>";*/
            }
        }
        if ($profil['kul_Soyad'] != $profilSoyad) {
            $kul_gunce_Soyad = $db->prepare("UPDATE kullanicilar SET kul_Soyad='" . $profilSoyad . "' WHERE kul_Id=?");
            $kul_guncellem_Soyad = $kul_gunce_Soyad->execute(array($_SESSION['kul_id']));
            if ($kul_guncellem_Soyad) {
                echo "<div class='alert alert-success'>Soyad Değişikliği İşleminiz Gerçekleşti.</div><meta http-equiv='refresh' content='1; url=profil.php'>";
            } else {
                echo "<div class='alert alert-danger'>Soyad Değişikliği İşleminiz Başarısız.</div>";
            }
            $ekle_log_Soyad = $db->prepare("INSERT INTO log(log_kul_id, log_eylem, log_aciklama) VALUES ('" . $_SESSION['kul_id'] . "','Profil Bilgi Güncelleme','" . $_SESSION['kul_id'] . " -Nolu kullanıcı " . $profil['kul_Ad'] . " " . $profil['kul_Soyad'] . ", soyadını " . $profilSoyad . " olarak değiştirdi')");
            $ekleme_log_Soyad = $ekle_log_Soyad->execute(array());
            if ($ekleme_log_Soyad) {
               /* echo "<div class='alert alert-success'>Log Ekleme İşlemi Tamamlandı.</div>";*/
            } else {
               /* echo "<div class='alert alert-danger'>Log Kayıt İşlemi Başarısız.</div>";*/
            }
        }
        if ($profil['kul_Eposta'] != $profilEposta) {
            $veri1 = $db->prepare('SELECT kul_Eposta FROM kullanicilar WHERE kul_Eposta=?');
            $veri1->execute(array($profilEposta));
            $v1 = $veri1->fetchAll(PDO::FETCH_ASSOC);
            $say1 = $veri1->rowCount();
            foreach ($v1 as $kul_kayit) ;
            if (!$say1) {
                $kul_gunce_Eposta = $db->prepare("UPDATE kullanicilar SET kul_Eposta='" . $profilEposta . "' WHERE kul_Id=?");
                $kul_guncellem_Eposta = $kul_gunce_Eposta->execute(array($_SESSION['kul_id']));
                if ($kul_guncellem_Eposta) {
                    echo "<div class='alert alert-success'>Eposta Değişikliği İşleminiz Gerçekleşti.</div><meta http-equiv='refresh' content='1; url=profil.php'>";
                } else {
                    echo "<div class='alert alert-danger'>Eposta Değişikliği İşleminiz Başarısız.</div>";
                }
                $ekle_log_Eposta = $db->prepare("INSERT INTO log(log_kul_id, log_eylem, log_aciklama) VALUES ('" . $_SESSION['kul_id'] . "','Profil Bilgi Güncelleme','" . $_SESSION['kul_id'] . " -Nolu kullanıcı " . $profil['kul_Ad'] . " " . $profil['kul_Soyad'] . ", " . $profil['kul_Eposta'] . " eposta adresini " . $profilEposta . " olarak değiştirdi')");
                $ekleme_log_Eposta = $ekle_log_Eposta->execute(array());
                if ($ekleme_log_Eposta) {
                   /* echo "<div class='alert alert-success'>Log Ekleme İşlemi Tamamlandı.</div>";*/
                } else {
                  /*  echo "<div class='alert alert-danger'>Log Kayıt İşlemi Başarısız.</div>";*/
                }
            } else {
                echo "<div class='alert alert-danger'>Girilen e-posta adresi başka bir kullanıcımız tarafından kullanılmakta.</div>";
            }
        }
        if ($profil['kul_CepNo'] != $profilCepNo) {
            $kul_gunce_Eposta = $db->prepare("UPDATE kullanicilar SET kul_CepNo='" . $profilCepNo . "' WHERE kul_Id=?");
            $kul_guncellem_Eposta = $kul_gunce_Eposta->execute(array($_SESSION['kul_id']));
            if ($kul_guncellem_Eposta) {
                echo "<div class='alert alert-success'>Cep no Değişikliği İşleminiz Gerçekleşti.</div><meta http-equiv='refresh' content='1; url=profil.php'>";
            } else {
                echo "<div class='alert alert-danger'>Cep no Değişikliği İşleminiz Başarısız.</div>";
            }
            $ekle_log_Eposta = $db->prepare("INSERT INTO log(log_kul_id, log_eylem, log_aciklama) VALUES ('" . $_SESSION['kul_id'] . "','Profil Bilgi Güncelleme','" . $_SESSION['kul_id'] . " -Nolu kullanıcı " . $profil['kul_Ad'] . " " . $profil['kul_Soyad'] . ", " . $profil['kul_CepNo'] . " cep numarasını " . $profilCepNo . " olarak değiştirdi')");
            $ekleme_log_Eposta = $ekle_log_Eposta->execute(array());
            if ($ekleme_log_Eposta) {
               /* echo "<div class='alert alert-success'>Log Ekleme İşlemi Tamamlandı.</div>";*/
            } else {
              /*  echo "<div class='alert alert-danger'>Log Kayıt İşlemi Başarısız.</div>";*/
            }
        }
        if ($profil['kul_DogumTar'] != $profilDogumTar) {
            $kul_gunce_DogumTar = $db->prepare("UPDATE kullanicilar SET kul_DogumTar='" . $profilDogumTar . "' WHERE kul_Id=?");
            $kul_guncellem_DogumTar = $kul_gunce_DogumTar->execute(array($_SESSION['kul_id']));
            if ($kul_guncellem_DogumTar) {
                echo "<div class='alert alert-success'>Doğum Tarihi Değişikliği İşleminiz Gerçekleşti.</div><meta http-equiv='refresh' content='1; url=profil.php'>";
            } else {
                echo "<div class='alert alert-danger'>Doğum Tarihi Değişikliği İşleminiz Başarısız.</div>";
            }
            $ekle_log_DogumTar = $db->prepare("INSERT INTO log(log_kul_id, log_eylem, log_aciklama) VALUES ('" . $_SESSION['kul_id'] . "','Profil Bilgi Güncelleme','" . $_SESSION['kul_id'] . " -Nolu kullanıcı " . $profil['kul_Ad'] . " " . $profil['kul_Soyad'] . ", " . $profil['kul_DogumTar'] . " doğum tarihini " . $profilDogumTar . " olarak değiştirdi')");
            $ekleme_log_DogumTar = $ekle_log_DogumTar->execute(array());
            if ($ekleme_log_DogumTar) {
             /*   echo "<div class='alert alert-success'>Log Ekleme İşlemi Tamamlandı.</div>";*/
            } else {
               /* echo "<div class='alert alert-danger'>Log Kayıt İşlemi Başarısız.</div>";*/
            }
        }
    }
}
if (g('islem') == 'profil_sifre_kaydet') {
    $sifre = p('sifre');
    $sifre_tekrar = p('sifre_tekrar');
    $id = p('kul_id');
    if (empty($sifre)) {
        echo "<div class='alert alert-warning'>Lütfen Şifre giriniz.</div>";
    } elseif (empty($sifre_tekrar)) {
        echo "<div class='alert alert-warning'>Lütfen Şifrenizi Tekrar giriniz.</div>";
    } elseif ($sifre != $sifre_tekrar) {
        echo "<div class='alert alert-warning'>Şifreler Uyumsuz.</div>";
    } else {
        $kul_gunce_Sifre = $db->prepare("UPDATE kullanicilar SET kul_Sifre='" . md5($sifre) . "',kul_Sifre_yeni=null WHERE kul_Id=?");
        $kul_guncellem_Sifre = $kul_gunce_Sifre->execute(array($_SESSION['kul_id']));
        if ($kul_guncellem_Sifre) {
            echo "<div class='alert alert-success'>Şifre Değişikliği İşleminiz Gerçekleşti.</div><meta http-equiv='refresh' content='1; url=profil.php'>";
        } else {
            echo "<div class='alert alert-danger'>Şifre Değişikliği İşleminiz Başarısız.</div>";
        }
        $ekle_log_Sifre = $db->prepare("INSERT INTO log(log_kul_id, log_eylem, log_aciklama) VALUES ('" . $_SESSION['kul_id'] . "','Profil Şifre Güncelleme','" . $_SESSION['kul_id'] . " -Nolu kullanıcı " . $_SESSION['isim'] . " " . $_SESSION['soyisim'] . ", şifresini değiştirdi')");
        $ekleme_log_Sifre = $ekle_log_Sifre->execute(array());
        if ($ekleme_log_Sifre) {
            /*echo "<div class='alert alert-success'>Log Ekleme İşlemi Tamamlandı.</div>";*/
        } else {
          /*  echo "<div class='alert alert-danger'>Log Kayıt İşlemi Başarısız.</div>";*/
        }
    }
}
/*HİSSE AL-SAT*/
if (g('islem') == 'hisse_satin_al') {
    $hisse_satin_al_kul_id = p('kul_id');
    $hisse_satin_al_sembol = p('sembol');
    $hisse_satin_al_tutar = p('alis_tutar');
    $hisse_satin_al_miktar = p('alis_miktar');
    $hisse_satin_al_komisyon = p('alis_komisyon');
    $hisse_satin_al_toplam = p('alis_toplam');
    $veri = $db->prepare('SELECT kul_Ad,kul_Soyad,kul_bakiye FROM kullanicilar WHERE kul_id=?');
    $veri->execute(array($hisse_satin_al_kul_id));
    $v = $veri->fetchAll(PDO::FETCH_ASSOC);
    foreach ($v as $kul_bilgilerim) ;
    $verim = $db->prepare('SELECT varlik_id,varlik_kul_id,varlik_hisse_sembol,varlik_alim_adet,varlik_satim_adet,varlik_elde FROM varliklar WHERE varlik_kul_id=? AND varlik_hisse_sembol=?');
    $verim->execute(array($hisse_satin_al_kul_id, $hisse_satin_al_sembol));
    $vm = $verim->fetchAll(PDO::FETCH_ASSOC);
    $say_ekle = $verim->rowCount();
    foreach ($vm as $varlık_bilgilerim) ;
    if (bakiye_son($hisse_satin_al_sembol) == $hisse_satin_al_tutar) {
        if ($kul_bilgilerim['kul_bakiye'] > $hisse_satin_al_toplam) {
            $yeni_bakiye = $kul_bilgilerim['kul_bakiye'] - $hisse_satin_al_toplam;
            $ekle = $db->prepare("INSERT INTO alim(alim_kul_id, alim_hisse_sembol, alim_hisse_deger, alim_hisse_komisyon, alim_hisse_lot,alim_lot_satilmayan,alim_hisse_toplam_tutar) VALUES ('" . $hisse_satin_al_kul_id . "','" . $hisse_satin_al_sembol . "','" . $hisse_satin_al_tutar . "','" . $hisse_satin_al_komisyon . "','" . $hisse_satin_al_miktar . "','" . $hisse_satin_al_miktar . "','" . $hisse_satin_al_toplam . "')");
            $ekleme = $ekle->execute(array());
            if ($say_ekle > 0) {
                $alim_adet = $varlık_bilgilerim['varlik_alim_adet'] + $hisse_satin_al_miktar;
                $elde = $varlık_bilgilerim['varlik_elde'] + $hisse_satin_al_miktar;
                $varlik_e = $db->prepare("UPDATE varliklar SET varlik_alim_adet='" . $alim_adet . "',varlik_elde='" . $elde . "' WHERE varlik_kul_id=? AND varlik_hisse_sembol=?");
                $varlik_ekl = $varlik_e->execute(array($hisse_satin_al_kul_id, $hisse_satin_al_sembol));
            } else {
                $varlik_e = $db->prepare("INSERT INTO varliklar(varlik_kul_id, varlik_hisse_sembol, varlik_alim_adet, varlik_elde) VALUES ('" . $hisse_satin_al_kul_id . "','" . $hisse_satin_al_sembol . "','" . $hisse_satin_al_miktar . "','" . $hisse_satin_al_miktar . "')");
                $varlik_ekl = $varlik_e->execute(array());
            }
            $guncelle = $db->prepare('UPDATE kullanicilar SET kul_bakiye=? WHERE kul_id=?');
            $guncelleme = $guncelle->execute(array($yeni_bakiye, $hisse_satin_al_kul_id));
            if ($ekleme && $guncelleme && $varlik_ekl) {
                echo "<div class='alert alert-success'>Satın Alma İşlemi Başarı ile Gerçekleşti.</div>";
                echo "<div class='alert alert-success'>Kalan Bakiyeniz: " . $yeni_bakiye . " &#x20BA;</div><meta http-equiv='refresh' content='1; url=index.php'>";
                $logekle = $db->prepare("INSERT INTO log(log_kul_id, log_eylem, log_aciklama) VALUES ('" . $hisse_satin_al_kul_id . "','Hisse Alım','" . $hisse_satin_al_kul_id . " -Nolu kullanıcı " . $kul_bilgilerim['kul_Ad'] . " " . $kul_bilgilerim['kul_Soyad'] . " " . $hisse_satin_al_sembol . " hissesini " . $hisse_satin_al_tutar . " TL tutardan " . $hisse_satin_al_miktar . " adet aldı. Bu işlem için " . $hisse_satin_al_komisyon . " TL komisyon ile " . $hisse_satin_al_toplam . " TL toplam tutar ödedi.')");
                $logekleme = $logekle->execute(array());
                if ($logekleme) {
                  /*  echo "<div class='alert alert-success'>Log Ekleme İşlemi Tamamlandı.</div>";*/
                } else {
                   /* echo "<div class='alert alert-danger'>Log Kayıt İşlemi Başarısız.</div>";*/
                }
            } else {
                echo "<div class='alert alert-danger'>Bir Hata Meydana Geldi.</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Yeterli Bakiye Mevcut Değil</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>Hisse Fiyatı Değişti</div>";
    }
}
if (g('islem') == 'hisse_sat') {
    $hisse_sat_kul_id = p('kul_id');
    $hisse_sat_sembol = p('sembol');
    $hisse_sat_tutar = p('sat_tutar');
    $hisse_sat_miktar = p('sat_miktar');
    $hisse_sat_komisyon = p('sat_komisyon');
    $hisse_sat_toplam = p('sat_toplam');
    $veri = $db->prepare('SELECT varlik_elde,varlik_satim_adet FROM varliklar WHERE varlik_kul_id=? and varlik_hisse_sembol=?');
    $veri->execute(array($hisse_sat_kul_id, $hisse_sat_sembol));
    $v = $veri->fetchAll(PDO::FETCH_ASSOC);
    foreach ($v as $varlik_elde) ;
    $veri = $db->prepare('SELECT kul_Ad,kul_Soyad,kul_bakiye FROM kullanicilar WHERE kul_id=?');
    $veri->execute(array($hisse_sat_kul_id));
    $v = $veri->fetchAll(PDO::FETCH_ASSOC);
    foreach ($v as $kul_bilgilerim) ;
    if ($varlik_elde['varlik_elde'] >= $hisse_sat_miktar) {/*sahte girişlere karşı veritabanı karşılaştırması*/
        if (bakiye_son($hisse_sat_sembol) == $hisse_sat_tutar) {/*hisse fiyatı değişti mi ? karşılaştırması*/
            $varlik_satim_guncel = $varlik_elde['varlik_satim_adet'] + $hisse_sat_miktar;
            $varlik_elde_guncel = $varlik_elde['varlik_elde'] - $hisse_sat_miktar;
            $yeni_bakiyem = $kul_bilgilerim['kul_bakiye'] + $hisse_sat_toplam;
            $toplam_kar_zarar = 0;
            $lot_degisen = $hisse_sat_miktar;
            $veri_kar_zarar = $db->prepare('SELECT alim_id,alim_hisse_lot,alim_lot_satilmayan,alim_hisse_toplam_tutar FROM alim WHERE alim_lot_satilmayan>0 AND alim_kul_id=? AND alim_hisse_sembol=? ORDER BY alim_zaman ASC');
            $veri_kar_zarar->execute(array($hisse_sat_kul_id, $hisse_sat_sembol));
            $v_kar_zarar = $veri_kar_zarar->fetchAll(PDO::FETCH_ASSOC);
            $say_kar_zarar = $veri_kar_zarar->rowCount();
            foreach ($v_kar_zarar as $kar_zarar) {
                if ($lot_degisen > 0) {
                    if ($kar_zarar['alim_hisse_lot'] >= $lot_degisen) {
                        $toplam_kar_zarar = $toplam_kar_zarar + $kar_zarar['alim_hisse_toplam_tutar'] * $lot_degisen / $kar_zarar['alim_hisse_lot'];
                        $alim_satilmayan_gunce = $db->prepare("UPDATE alim SET alim_lot_satilmayan='" . ($kar_zarar['alim_lot_satilmayan'] - $lot_degisen) . "' WHERE alim_id=?");
                        $alim_satilmayan_guncellemem = $alim_satilmayan_gunce->execute(array($kar_zarar['alim_id']));
                        break;
                    } else {
                        $toplam_kar_zarar = $toplam_kar_zarar + $kar_zarar['alim_hisse_toplam_tutar'];
                        $lot_degisen = $lot_degisen - $kar_zarar['alim_hisse_lot'];
                        $alim_satilmayan_gunc = $db->prepare("UPDATE alim SET alim_lot_satilmayan=0 WHERE alim_id=?");
                        $alim_satilmayan_guncelleme = $alim_satilmayan_gunc->execute(array($kar_zarar['alim_id']));
                    }
                } else {
                    echo "<div class='alert alert-success'>Hata .</div>";
                }
            }
            $kar_zarar_durum = ($hisse_sat_toplam - $toplam_kar_zarar);
            $satekle = $db->prepare("INSERT INTO satim(satim_kul_id, satim_hisse_sembol, satim_hisse_deger, satim_hisse_komisyon, satim_hisse_lot,satim_kar_zarar, satim_hisse_toplam_tutar) VALUES ('" . $hisse_sat_kul_id . "','" . $hisse_sat_sembol . "','" . $hisse_sat_tutar . "','" . $hisse_sat_komisyon . "','" . $hisse_sat_miktar . "','" . $kar_zarar_durum . "','" . $hisse_sat_toplam . "')");
            $satekleme = $satekle->execute(array());
            $varlikguncelle = $db->prepare("UPDATE varliklar SET varlik_satim_adet='" . $varlik_satim_guncel . "',varlik_elde='" . $varlik_elde_guncel . "' WHERE varlik_kul_id=? AND varlik_hisse_sembol=?");
            $varlikguncelleme = $varlikguncelle->execute(array($hisse_sat_kul_id, $hisse_sat_sembol));
            $bakiyegun = $db->prepare('UPDATE kullanicilar SET kul_Bakiye=? WHERE kul_Id=?');
            $bakiyeguncel = $bakiyegun->execute(array($yeni_bakiyem, $hisse_sat_kul_id));
            if ($satekleme && $varlikguncelleme && $bakiyeguncel) {
                echo "<div class='alert alert-success'>Satım İşlemi Başarı ile Gerçekleşti.</div>";
                echo "<div class='alert alert-success'>Kalan Hisse Miktarı: " . $varlik_elde_guncel . "</div>";
                echo "<div class='alert alert-success'>Bakiyeniz: " . $yeni_bakiyem . "</div><meta http-equiv='refresh' content='1; url=index.php'>";
                $ekle = $db->prepare("INSERT INTO log(log_kul_id, log_eylem, log_aciklama) VALUES ('" . $hisse_sat_kul_id . "','Hisse Satım','" . $hisse_sat_kul_id . " -Nolu kullanıcı " . $kul_bilgilerim['kul_Ad'] . " " . $kul_bilgilerim['kul_Soyad'] . " " . $hisse_sat_sembol . " hissesini " . $hisse_sat_tutar . " TL tutardan " . $hisse_sat_miktar . " adet sattı. Bu işlem için " . $hisse_sat_komisyon . " TL komisyon ödedi. " . $hisse_sat_toplam . " TL toplam tutar aldı.')");
                $ekleme = $ekle->execute(array());
                if ($ekleme) {
                   /* echo "<div class='alert alert-success'>Log Ekleme İşlemi Tamamlandı.</div>";*/
                } else {
                  /*  echo "<div class='alert alert-danger'>Log Kayıt İşlemi Başarısız.</div>";*/
                }
            } else {
                echo "<div class='alert alert-danger'>İşlem Başarısız.</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Hisse Fiyatı Değişti</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>Girilen Hisse Miktarı Portföyünüzde Bulunmamakta.</div>";
    }
}
if (g('islem') == 'hisse_sat_aktif_varlik') {
    $hisse_sat_kul_id = p('kul_id');
    $hisse_sat_sembol = p('sembol');
    $hisse_sat_tutar = p('sat_tutar');
    $hisse_sat_miktar = p('sat_miktar');
    $hisse_sat_komisyon = p('sat_komisyon');
    $hisse_sat_toplam = p('sat_toplam');
    $veri = $db->prepare('SELECT varlik_elde,varlik_satim_adet FROM varliklar WHERE varlik_kul_id=? and varlik_hisse_sembol=?');
    $veri->execute(array($hisse_sat_kul_id, $hisse_sat_sembol));
    $v = $veri->fetchAll(PDO::FETCH_ASSOC);
    foreach ($v as $varlik_elde) ;
    $veri = $db->prepare('SELECT kul_Ad,kul_Soyad,kul_bakiye FROM kullanicilar WHERE kul_id=?');
    $veri->execute(array($hisse_sat_kul_id));
    $v = $veri->fetchAll(PDO::FETCH_ASSOC);
    foreach ($v as $kul_bilgilerim) ;
    if ($varlik_elde['varlik_elde'] >= $hisse_sat_miktar) {/*sahte girişlere karşı veritabanı karşılaştırması*/
        if (bakiye_son($hisse_sat_sembol) == $hisse_sat_tutar) {/*hisse fiyatı değişti mi ? karşılaştırması*/
            $varlik_satim_guncel = $varlik_elde['varlik_satim_adet'] + $hisse_sat_miktar;
            $varlik_elde_guncel = $varlik_elde['varlik_elde'] - $hisse_sat_miktar;
            $yeni_bakiyem = $kul_bilgilerim['kul_bakiye'] + $hisse_sat_toplam;
            $toplam_kar_zarar = 0;
            $lot_degisen = $hisse_sat_miktar;
            $veri_kar_zarar = $db->prepare('SELECT alim_id,alim_hisse_lot,alim_lot_satilmayan,alim_hisse_toplam_tutar FROM alim WHERE alim_lot_satilmayan>0 AND alim_kul_id=? AND alim_hisse_sembol=? ORDER BY alim_zaman ASC');
            $veri_kar_zarar->execute(array($hisse_sat_kul_id, $hisse_sat_sembol));
            $v_kar_zarar = $veri_kar_zarar->fetchAll(PDO::FETCH_ASSOC);
            $say_kar_zarar = $veri_kar_zarar->rowCount();
            foreach ($v_kar_zarar as $kar_zarar) {
                if ($lot_degisen > 0) {
                    if ($kar_zarar['alim_hisse_lot'] >= $lot_degisen) {
                        $toplam_kar_zarar = $toplam_kar_zarar + $kar_zarar['alim_hisse_toplam_tutar'] * $lot_degisen / $kar_zarar['alim_hisse_lot'];
                        $alim_satilmayan_gunce = $db->prepare("UPDATE alim SET alim_lot_satilmayan='" . ($kar_zarar['alim_lot_satilmayan'] - $lot_degisen) . "' WHERE alim_id=?");
                        $alim_satilmayan_guncellemem = $alim_satilmayan_gunce->execute(array($kar_zarar['alim_id']));
                        break;
                    } else {
                        $toplam_kar_zarar = $toplam_kar_zarar + $kar_zarar['alim_hisse_toplam_tutar'];
                        $lot_degisen = $lot_degisen - $kar_zarar['alim_hisse_lot'];
                        $alim_satilmayan_gunc = $db->prepare("UPDATE alim SET alim_lot_satilmayan=0 WHERE alim_id=?");
                        $alim_satilmayan_guncelleme = $alim_satilmayan_gunc->execute(array($kar_zarar['alim_id']));
                    }
                } else {
                    echo "<div class='alert alert-success'>Hata .</div>";
                }
            }
            $kar_zarar_durum = ($hisse_sat_toplam - $toplam_kar_zarar);
            $satekle = $db->prepare("INSERT INTO satim(satim_kul_id, satim_hisse_sembol, satim_hisse_deger, satim_hisse_komisyon, satim_hisse_lot,satim_kar_zarar, satim_hisse_toplam_tutar) VALUES ('" . $hisse_sat_kul_id . "','" . $hisse_sat_sembol . "','" . $hisse_sat_tutar . "','" . $hisse_sat_komisyon . "','" . $hisse_sat_miktar . "','" . $kar_zarar_durum . "','" . $hisse_sat_toplam . "')");
            $satekleme = $satekle->execute(array());
            $varlikguncelle = $db->prepare("UPDATE varliklar SET varlik_satim_adet='" . $varlik_satim_guncel . "',varlik_elde='" . $varlik_elde_guncel . "' WHERE varlik_kul_id=? AND varlik_hisse_sembol=?");
            $varlikguncelleme = $varlikguncelle->execute(array($hisse_sat_kul_id, $hisse_sat_sembol));
            $bakiyegun = $db->prepare('UPDATE kullanicilar SET kul_Bakiye=? WHERE kul_Id=?');
            $bakiyeguncel = $bakiyegun->execute(array($yeni_bakiyem, $hisse_sat_kul_id));
            if ($satekleme && $varlikguncelleme && $bakiyeguncel) {
                echo "<div class='alert alert-success'>Satım İşlemi Başarı ile Gerçekleşti.</div>";
                echo "<div class='alert alert-success'>Kalan Hisse Miktarı: " . $varlik_elde_guncel . "</div>";
                echo "<div class='alert alert-success'>Bakiyeniz: " . $yeni_bakiyem . "</div><meta http-equiv='refresh' content='1; url=aktif_varliklar.php'>";
                $ekle = $db->prepare("INSERT INTO log(log_kul_id, log_eylem, log_aciklama) VALUES ('" . $hisse_sat_kul_id . "','Hisse Satım','" . $hisse_sat_kul_id . " -Nolu kullanıcı " . $kul_bilgilerim['kul_Ad'] . " " . $kul_bilgilerim['kul_Soyad'] . " " . $hisse_sat_sembol . " hissesini " . $hisse_sat_tutar . " TL tutardan " . $hisse_sat_miktar . " adet sattı. Bu işlem için " . $hisse_sat_komisyon . " TL komisyon ödedi. " . $hisse_sat_toplam . " TL toplam tutar aldı.')");
                $ekleme = $ekle->execute(array());
                if ($ekleme) {
                   /* echo "<div class='alert alert-success'>Log Ekleme İşlemi Tamamlandı.</div>";*/
                } else {
                   /* echo "<div class='alert alert-danger'>Log Kayıt İşlemi Başarısız.</div>";*/
                }
            } else {
                echo "<div class='alert alert-danger'>İşlem Başarısız.</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Hisse Fiyatı Değişti. Sayfayı güncelleyiniz.</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>Girilen Hisse Miktarı Portföyünüzde Bulunmamakta.</div>";
    }
}
/*HİSSE BİLGİLERİ YÜKLEME*/
if (g('islem') == 'tablo_bilgi_al') {
    $link = "http://bigpara.hurriyet.com.tr/borsa/canli-borsa/";
    $icerik = file_get_contents($link);
    $icerik = preg_replace('~[\r\n]~', '', $icerik);
    $icerik = preg_replace('~[ ]~', '', $icerik);
    $h_td_sembol = array();
    $h_td_sembol = ara('target="_blank">', '</a>', $icerik);
    $h_td_sembol = ara('target="_blank">', '</a>', $icerik);/*hisse adlarının dizisi[0] - [99] arası 100 hisse*/
    $tum_hisse_dizileri = array();
    for ($sayi = 0; $sayi < 100; $sayi++) {
        $hisse_tekil = array();
        $h_td_yuzde_id_deger = ara('h_td_yuzde_id_' . $h_td_sembol[$sayi] . '">', '</li>', $icerik);
        $h_td_fiyat_id_deger = ara('h_td_fiyat_id_' . $h_td_sembol[$sayi] . '">', '</li>', $icerik);
        $h_td_farktl_id_deger = ara('h_td_farktl_id_' . $h_td_sembol[$sayi] . '">', '</li>', $icerik);
        $h_td_dusuk_id_deger = ara('h_td_dusuk_id_' . $h_td_sembol[$sayi] . '">', '</li>', $icerik);
        $h_td_yuksek_id_deger = ara('h_td_yuksek_id_' . $h_td_sembol[$sayi] . '">', '</li>', $icerik);
        $h_td_hacimlot_id_deger = ara('h_td_hacimlot_id_' . $h_td_sembol[$sayi] . '">', '</li>', $icerik);
        $h_td_hacimtl_id_deger = ara('h_td_hacimtl_id_' . $h_td_sembol[$sayi] . '">', '</li>', $icerik);
        $h_td_saat_id_deger = ara('h_td_saat_id_' . $h_td_sembol[$sayi] . '">', '</li>', $icerik);
        array_push($hisse_tekil, $h_td_sembol[$sayi], $h_td_yuzde_id_deger, $h_td_fiyat_id_deger, $h_td_farktl_id_deger, $h_td_dusuk_id_deger, $h_td_yuksek_id_deger, $h_td_hacimlot_id_deger, $h_td_hacimtl_id_deger, $h_td_saat_id_deger);
        array_push($tum_hisse_dizileri, $hisse_tekil);
    }
    echo json_encode($tum_hisse_dizileri);
}
if (g('islem') == 'tablo_yukselen_dusen') {
    $link = "http://bigpara.hurriyet.com.tr/borsa/canli-borsa/";
    $icerik = file_get_contents($link);
    $icerik = preg_replace('~[\r\n]~', '', $icerik);
    $icerik = preg_replace('~[ ]~', '', $icerik);
    $h_td_sembol = array();
    $h_td_sembol = ara('target="_blank">', '</a>', $icerik);
    $h_td_sembol = ara('target="_blank">', '</a>', $icerik);/*hisse adlarının dizisi[0] - [99] arası 100 hisse*/
    $tum_hisse_dizileri = array();
    for ($sayi = 0; $sayi < 100; $sayi++) {
        $tum_hiss = array();
        $hisse_tekil_yuzde = ara('h_td_yuzde_id_' . $h_td_sembol[$sayi] . '">', '</li>', $icerik);
        $hisse_tekil_fiyat = ara('h_td_fiyat_id_' . $h_td_sembol[$sayi] . '">', '</li>', $icerik);
        array_push($tum_hiss, $h_td_sembol[$sayi], convert_virgül_nokta($hisse_tekil_yuzde[0]), convert_virgül_nokta($hisse_tekil_fiyat[0]));
        array_push($tum_hisse_dizileri, $tum_hiss);
    }
    $sorted = val_sort($tum_hisse_dizileri, 1);/*1=yuzde*/
    echo json_encode($sorted);
}
/*ÖDÜL İŞLEMLERİ*/
if (g('islem') == 'odul_sorgulama') {
    /*ÖLÜL KULLANICI HAFTALIK*/
    $veri_odul_kul_haf = $db->prepare('SELECT * FROM `oduller`WHERE `odul_zaman` between DATE_SUB(CURDATE(), INTERVAL 1 WEEK) 
and CURRENT_TIMESTAMP and `odul_tur`="haftalik_kullanici"');
    $veri_odul_kul_haf->execute(array());
    $say_odul_kul_haf = $veri_odul_kul_haf->rowCount();
    if ($say_odul_kul_haf==0) {
        $sayi = 0;
        $veri_hafta_kul_sira = $db->prepare('SELECT kullanicilar.kul_Id as id, kullanicilar.kul_Bakiye as bakiye , satim.satim_kar_zarar as durum 
FROM satim INNER JOIN kullanicilar ON kullanicilar.kul_Id = satim.satim_kul_id WHERE satim_zaman 
between DATE_SUB("' . date('Y-m-d H:i:s', strtotime('last Sunday')) . '", INTERVAL 1 WEEK) 
and "' . date('Y-m-d H:i:s', strtotime('last Sunday')) . '" GROUP BY satim_kul_id ORDER BY satim.satim_kar_zarar DESC LIMIT 10');
        $veri_hafta_kul_sira->execute(array());
        $v_hafta_kul_sira = $veri_hafta_kul_sira->fetchAll(PDO::FETCH_ASSOC);
        foreach ($v_hafta_kul_sira as $hafta_kul_sira) {
            if ($sayi == 0) {
                odul_yaz($hafta_kul_sira['id'],$sayi,'haftalik_kullanici',$hafta_kul_sira['bakiye'],800,$hafta_kul_sira['durum']);
            } elseif ($sayi == 1) {
                odul_yaz($hafta_kul_sira['id'],$sayi,'haftalik_kullanici',$hafta_kul_sira['bakiye'],400,$hafta_kul_sira['durum']);
            } elseif ($sayi == 2) {
                odul_yaz($hafta_kul_sira['id'],$sayi,'haftalik_kullanici',$hafta_kul_sira['bakiye'],200,$hafta_kul_sira['durum']);
            } elseif ($sayi == 3 || $sayi == 4) {
                odul_yaz($hafta_kul_sira['id'],$sayi,'haftalik_kullanici',$hafta_kul_sira['bakiye'],150,$hafta_kul_sira['durum']);
            } else {
                odul_yaz($hafta_kul_sira['id'],$sayi,'haftalik_kullanici',$hafta_kul_sira['bakiye'],100,$hafta_kul_sira['durum']);
            }
            $sayi++;
        }
    }
    /*ÖDÜL KULLANICI AYLIK*/
    $veri_odul_ay_kul = $db->prepare('SELECT * FROM `oduller`WHERE `odul_zaman` between DATE_SUB(CURDATE(), INTERVAL 1 MONTH) 
and CURRENT_TIMESTAMP and `odul_tur`="aylik_kullanici"');
    $veri_odul_ay_kul->execute(array());
    $say_odul_ay_kul = $veri_odul_ay_kul->rowCount();
    if ($say_odul_ay_kul==0) {
        $sayi = 0;
        $veri_ay_kul_sira = $db->prepare('SELECT kullanicilar.kul_Id as id, kullanicilar.kul_Bakiye as bakiye , satim.satim_kar_zarar as durum 
FROM satim INNER JOIN kullanicilar ON kullanicilar.kul_Id = satim.satim_kul_id WHERE satim_zaman 
between "' . (new \DateTime('first day of previous month 00:00:00', new DateTimeZone('UTC')))->format('Y-m-d H:i:s'). PHP_EOL . '" 
and "' . date("Y-m-d H:i:s", strtotime("1 ".date("M Y", time()))) . '" GROUP BY satim_kul_id ORDER BY satim.satim_kar_zarar DESC LIMIT 10');
        $veri_ay_kul_sira->execute(array());
        $v_ay_kul_sira = $veri_ay_kul_sira->fetchAll(PDO::FETCH_ASSOC);
        foreach ($v_ay_kul_sira as $ay_kul_sira) {
            if ($sayi == 0) {

                odul_yaz($ay_kul_sira['id'],$sayi,'aylik_kullanici',$ay_kul_sira['bakiye'],800,$ay_kul_sira['durum']);
            } elseif ($sayi == 1) {
                odul_yaz($ay_kul_sira['id'],$sayi,'aylik_kullanici',$ay_kul_sira['bakiye'],400,$ay_kul_sira['durum']);
            } elseif ($sayi == 2) {
                odul_yaz($ay_kul_sira['id'],$sayi,'aylik_kullanici',$ay_kul_sira['bakiye'],200,$ay_kul_sira['durum']);
            } elseif ($sayi == 3 || $sayi == 4) {
                odul_yaz($ay_kul_sira['id'],$sayi,'aylik_kullanici',$ay_kul_sira['bakiye'],150,$ay_kul_sira['durum']);
            } else {
                odul_yaz($ay_kul_sira['id'],$sayi,'aylik_kullanici',$ay_kul_sira['bakiye'],100,$ay_kul_sira['durum']);
            }
            $sayi++;
        }
    }
    /*ÖDÜL HAFTALIK LİG*/
    $veri_odul_haf_lig = $db->prepare('SELECT * FROM `oduller`WHERE `odul_zaman` between DATE_SUB(CURDATE(), INTERVAL 1 WEEK) 
and CURRENT_TIMESTAMP and `odul_tur`="haftalik_lig"');
    $veri_odul_haf_lig->execute(array());
    $say_odul_haf_lig = $veri_odul_haf_lig->rowCount();
    if ($say_odul_haf_lig==0) {
        $sayi = 0;
        $veri_hafta_lig_sira = $db->prepare('SELECT ligler.lig_id as l_id, ligler.lig_yonetici_id as l_y_id, SUM(satim.satim_kar_zarar) 
AS durum FROM satim INNER JOIN kullanicilar ON kullanicilar.kul_Id = satim.satim_kul_id INNER JOIN ligler ON ligler.lig_id = kullanicilar.kul_lig_id 
WHERE satim_zaman between DATE_SUB("' . date('Y-m-d H:i:s', strtotime('last Sunday')) . '", INTERVAL 1 WEEK) 
and "' . date('Y-m-d H:i:s', strtotime('last Sunday')) . '" GROUP BY ligler.lig_id ORDER BY durum DESC LIMIT 10');
        $veri_hafta_lig_sira->execute(array());
        $v_hafta_lig_sira = $veri_hafta_lig_sira->fetchAll(PDO::FETCH_ASSOC);
        foreach ($v_hafta_lig_sira as $hafta_lig_sira) {
            /**/
            if ($sayi == 0) {
                $veri_kul = $db->prepare('SELECT `kul_Id` as id,`kul_Bakiye` as bakiye FROM `kullanicilar` WHERE `kul_lig_id`=?');
                $veri_kul->execute(array($hafta_lig_sira['l_id']));
                $v_kul = $veri_kul->fetchAll(PDO::FETCH_ASSOC);
                foreach ($v_kul as $kul) {
                    if ($kul['id'] == $hafta_lig_sira['l_y_id']) {
                        odul_yaz($kul['id'],$sayi,'haftalik_lig',$kul['bakiye'],900,$hafta_lig_sira['durum']);
                    }
                    else{
                        odul_yaz($kul['id'],$sayi,'haftalik_lig',$kul['bakiye'],800,$hafta_lig_sira['durum']);
                    }
                }
            }
            elseif ($sayi == 1) {
                $veri_kul = $db->prepare('SELECT `kul_Id` as id,`kul_Bakiye` as bakiye FROM `kullanicilar` WHERE `kul_lig_id`=?');
                $veri_kul->execute(array($hafta_lig_sira['l_id']));
                $v_kul = $veri_kul->fetchAll(PDO::FETCH_ASSOC);
                foreach ($v_kul as $kul) {
                    if ($kul['id'] == $hafta_lig_sira['l_y_id']) {
                        odul_yaz($kul['id'],$sayi,'haftalik_lig',$kul['bakiye'],500,$hafta_lig_sira['durum']);
                    }
                    else{
                        odul_yaz($kul['id'],$sayi,'haftalik_lig',$kul['bakiye'],400,$hafta_lig_sira['durum']);
                    }
                }
            }
            elseif ($sayi == 2) {
                $veri_kul = $db->prepare('SELECT `kul_Id` as id,`kul_Bakiye` as bakiye FROM `kullanicilar` WHERE `kul_lig_id`=?');
                $veri_kul->execute(array($hafta_lig_sira['l_id']));
                $v_kul = $veri_kul->fetchAll(PDO::FETCH_ASSOC);
                foreach ($v_kul as $kul) {
                    if ($kul['id'] == $hafta_lig_sira['l_y_id']) {
                        odul_yaz($kul['id'],$sayi,'haftalik_lig',$kul['bakiye'],300,$hafta_lig_sira['durum']);
                    }
                    else{
                        odul_yaz($kul['id'],$sayi,'haftalik_lig',$kul['bakiye'],200,$hafta_lig_sira['durum']);
                    }
                }
            }
            elseif ($sayi == 3 || $sayi == 4) {
                $veri_kul = $db->prepare('SELECT `kul_Id` as id,`kul_Bakiye` as bakiye FROM `kullanicilar` WHERE `kul_lig_id`=?');
                $veri_kul->execute(array($hafta_lig_sira['l_id']));
                $v_kul = $veri_kul->fetchAll(PDO::FETCH_ASSOC);
                foreach ($v_kul as $kul) {
                    if ($kul['id'] == $hafta_lig_sira['l_y_id']) {
                        odul_yaz($kul['id'],$sayi,'haftalik_lig',$kul['bakiye'],250,$hafta_lig_sira['durum']);
                    }
                    else{
                        odul_yaz($kul['id'],$sayi,'haftalik_lig',$kul['bakiye'],150,$hafta_lig_sira['durum']);
                    }
                }
            }
            else {
                $veri_kul = $db->prepare('SELECT `kul_Id` as id,`kul_Bakiye` as bakiye FROM `kullanicilar` WHERE `kul_lig_id`=?');
                $veri_kul->execute(array($hafta_lig_sira['l_id']));
                $v_kul = $veri_kul->fetchAll(PDO::FETCH_ASSOC);
                foreach ($v_kul as $kul) {
                    if ($kul['id'] == $hafta_lig_sira['l_y_id']) {
                        odul_yaz($kul['id'],$sayi,'haftalik_lig',$kul['bakiye'],200,$hafta_lig_sira['durum']);
                    }
                    else{
                        odul_yaz($kul['id'],$sayi,'haftalik_lig',$kul['bakiye'],100,$hafta_lig_sira['durum']);
                    }
                }
            }
            $sayi++;
        }
    }
    /*ÖDÜL AYLIK LİG*/
    $veri_odul_ay_lig = $db->prepare('SELECT * FROM `oduller`WHERE `odul_zaman` between DATE_SUB(CURDATE(), INTERVAL 1 MONTH) 
and CURRENT_TIMESTAMP and `odul_tur`="aylik_lig"');
    $veri_odul_ay_lig->execute(array());
    $say_odul_ay_lig = $veri_odul_ay_lig->rowCount();
    if ($say_odul_ay_lig==0) {
        $sayi = 0;
        $veri_ay_lig_sira = $db->prepare('SELECT ligler.lig_id as l_id, ligler.lig_yonetici_id as l_y_id, SUM(satim.satim_kar_zarar) 
AS durum FROM satim INNER JOIN kullanicilar ON kullanicilar.kul_Id = satim.satim_kul_id INNER JOIN ligler ON ligler.lig_id = kullanicilar.kul_lig_id 
WHERE satim_zaman between "' . (new \DateTime('first day of previous month 00:00:00', new DateTimeZone('UTC')))->format('Y-m-d H:i:s'). PHP_EOL . '" 
and "' . date("Y-m-d H:i:s", strtotime("1 ".date("M Y", time()))) . '" GROUP BY ligler.lig_id ORDER BY durum DESC LIMIT 10');
        $veri_ay_lig_sira->execute(array());
        $v_ay_lig_sira = $veri_ay_lig_sira->fetchAll(PDO::FETCH_ASSOC);
        foreach ($v_ay_lig_sira as $ay_lig_sira) {
            if ($sayi == 0) {
                $veri_kul = $db->prepare('SELECT `kul_Id` as id,`kul_Bakiye` as bakiye FROM `kullanicilar` WHERE `kul_lig_id`=?');
                $veri_kul->execute(array($ay_lig_sira['l_id']));
                $v_kul = $veri_kul->fetchAll(PDO::FETCH_ASSOC);
                foreach ($v_kul as $kul) {
                    if ($kul['id'] == $ay_lig_sira['l_y_id']) {
                        odul_yaz($kul['id'],$sayi,'aylik_lig',$kul['bakiye'],900,$ay_lig_sira['durum']);
                    }
                    else{
                        odul_yaz($kul['id'],$sayi,'aylik_lig',$kul['bakiye'],800,$ay_lig_sira['durum']);
                    }
                }
            }
            elseif ($sayi == 1) {
                $veri_kul = $db->prepare('SELECT `kul_Id` as id,`kul_Bakiye` as bakiye FROM `kullanicilar` WHERE `kul_lig_id`=?');
                $veri_kul->execute(array($ay_lig_sira['l_id']));
                $v_kul = $veri_kul->fetchAll(PDO::FETCH_ASSOC);
                foreach ($v_kul as $kul) {
                    if ($kul['id'] == $ay_lig_sira['l_y_id']) {
                        odul_yaz($kul['id'],$sayi,'aylik_lig',$kul['bakiye'],500,$ay_lig_sira['durum']);
                    }
                    else{
                        odul_yaz($kul['id'],$sayi,'aylik_lig',$kul['bakiye'],400,$ay_lig_sira['durum']);
                    }
                }
            }
            elseif ($sayi == 2) {
                $veri_kul = $db->prepare('SELECT `kul_Id` as id,`kul_Bakiye` as bakiye FROM `kullanicilar` WHERE `kul_lig_id`=?');
                $veri_kul->execute(array($ay_lig_sira['l_id']));
                $v_kul = $veri_kul->fetchAll(PDO::FETCH_ASSOC);
                foreach ($v_kul as $kul) {
                    if ($kul['id'] == $ay_lig_sira['l_y_id']) {
                        odul_yaz($kul['id'],$sayi,'aylik_lig',$kul['bakiye'],300,$ay_lig_sira['durum']);
                    }
                    else{
                        odul_yaz($kul['id'],$sayi,'aylik_lig',$kul['bakiye'],200,$ay_lig_sira['durum']);
                    }
                }
            }
            elseif ($sayi == 3 || $sayi == 4) {
                $veri_kul = $db->prepare('SELECT `kul_Id` as id,`kul_Bakiye` as bakiye FROM `kullanicilar` WHERE `kul_lig_id`=?');
                $veri_kul->execute(array($ay_lig_sira['l_id']));
                $v_kul = $veri_kul->fetchAll(PDO::FETCH_ASSOC);
                foreach ($v_kul as $kul) {
                    if ($kul['id'] == $ay_lig_sira['l_y_id']) {
                        odul_yaz($kul['id'],$sayi,'aylik_lig',$kul['bakiye'],250,$ay_lig_sira['durum']);
                    }
                    else{
                        odul_yaz($kul['id'],$sayi,'aylik_lig',$kul['bakiye'],150,$ay_lig_sira['durum']);
                    }
                }
            }
            else {
                $veri_kul = $db->prepare('SELECT `kul_Id` as id,`kul_Bakiye` as bakiye FROM `kullanicilar` WHERE `kul_lig_id`=?');
                $veri_kul->execute(array($ay_lig_sira['l_id']));
                $v_kul = $veri_kul->fetchAll(PDO::FETCH_ASSOC);
                foreach ($v_kul as $kul) {
                    if ($kul['id'] == $ay_lig_sira['l_y_id']) {
                        odul_yaz($kul['id'],$sayi,'aylik_lig',$kul['bakiye'],200,$ay_lig_sira['durum']);
                    }
                    else{
                        odul_yaz($kul['id'],$sayi,'aylik_lig',$kul['bakiye'],100,$ay_lig_sira['durum']);
                    }
                }
            }
            $sayi++;
        }
    }
}


?>