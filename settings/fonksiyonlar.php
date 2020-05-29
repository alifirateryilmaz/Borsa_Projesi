<?php
function ara($bas, $son, $yazi)
{
    @preg_match_all('/' . preg_quote($bas, '/') .
        '(.*?)' . preg_quote($son, '/') . '/i', $yazi, $m);
    return @$m[1];
}

function temiz($text)
{
    $text = strip_tags($text);
    $text = preg_replace('/<a\s+.*?href="([^")]+)"[^>]*>([^<]+)<\/a>/is', '\2 (\1)', $text);
    $text = preg_replace('/<!--.+?-->/', '', $text);
    $text = preg_replace('/{.+?}/', '', $text);
    $text = preg_replace('/&nbsp;/', ' ', $text);
    $text = preg_replace('/&amp;/', ' ', $text);
    $text = preg_replace('/&quot;/', ' ', $text);
    $text = htmlspecialchars($text);
    $text = addslashes($text);
    return $text;
}

function g($par)
{
    $par = temiz(@$_GET[$par]);
    return $par;
}

function p($par)
{
    $par = htmlspecialchars(addslashes(trim($_POST[$par])));
    return $par;
}
///////////////////////SESSİON
function s($par)
{
    $session = $_SESSION[$par];
    return $session;
}
///////////////////////YONETİCİ
function yoneticikontrol()
{

    if ($_SESSION['yetki'] == '1') {
    } else {
        header("Location:index.php");
        exit;
    }
}
/////////////////////////////KULLANICI KONTROL
function kullanicikontrol()
{

    if ($_SESSION['yetki'] == '0' || $_SESSION['yetki'] == '1') {
    } else {
        header("Location:index.php");
        exit;
    }
}
/////////////////////////////
function convert_virgül_nokta($data)
{
    if (strpos($data, ",")) {
        $chng = str_replace(",", ".", $data);
        $data = $chng;
    }
    return $data;
}

function convert_nokta_virgül($data)
{
    if (strpos($data, ".")) {
        $chng = str_replace(".", ",", $data);
        $data = $chng;
    }
    return $data;
}

function val_sort($array, $key)
{
    //Loop through and get the values of our specified key
    foreach ($array as $k => $v) {
        $b[] = strtolower($v[$key]);
    }
    //print_r($b);
    asort($b);
    //echo '<br />';
    //print_r($b);
    foreach ($b as $k => $v) {
        $c[] = $array[$k];
    }
    return $c;
}

function bakiye_son($sembol)
{
    $link = "http://bigpara.hurriyet.com.tr/borsa/canli-borsa/";
    $icerik = file_get_contents($link);
    $h_td_fiyat_id_deger = ara('h_td_fiyat_id_' . $sembol . '">', '</li>', $icerik);
    return convert_virgül_nokta($h_td_fiyat_id_deger[0]);
}

function rasgeleharf($kackarakter)
{
    $s = "";
    $char = "ABCDEFGHIJKLMNOPRSTUVWYZQX";
    for ($k = 1; $k <= $kackarakter; $k++) {
        $h = substr($char, mt_rand(0, strlen($char) - 1), 1);
        $s .= $h;
    }
    return $s;
}

function uzanti($dosya)
{
    $uzanti = pathinfo($dosya);
    $uzanti = $uzanti["extension"];
    return $uzanti;
}

function resimadi()
{
    $rn = rand(1000, 9999);
    $rn .= rasgeleharf(1);
    $rn .= rand(1000, 9999);
    $rn .= rasgeleharf(2);
    $rn .= rasgeleharf(2);
    $rn .= rand(1000, 9999);
    $rn .= rasgeleharf(1);
    $rn .= rand(1000, 9999);
    $rn .= rasgeleharf(2);
    $rn .= rasgeleharf(2);
    $rn .= rand(1000, 9999);
    $rn .= rasgeleharf(1);
    $rn .= rand(1000, 9999);
    $rn .= rasgeleharf(2);
    $rn .= rasgeleharf(2);
    $rn .= rand(1000, 9999);
    $rn .= rasgeleharf(1);
    $rn .= rand(1000, 9999);
    $rn .= rasgeleharf(2);
    $rn .= rasgeleharf(2);
    $rn .= rand(1000, 9999);
    $rn .= rasgeleharf(1);
    return $rn;
}

function resimyukle($postisim, $yeniisim, $yol)
{
    // VEROT RESİM YÜKLEME
    $foo = new Upload($_FILES[$postisim]);
    if ($foo->uploaded) {
        $foo->allowed = array('image/*');
        $foo->file_new_name_body = $yeniisim;
        $foo->image_resize = true;
        $foo->image_x = 500;
        $foo->image_ratio_y = true;
        $foo->Process($yol);
        if ($foo->processed) {
            $foo->Clean();
            return true;
        } else {
            return false;
        }
    }
    // VEROT RESİM YÜKLEME
}
?>