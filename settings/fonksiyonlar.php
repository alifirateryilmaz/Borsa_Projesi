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
/*function yoneticikontrol()
{
    if (!$_SESSION || !$_SESSION['yetki'] == '1') {
        header("Location:giris.php");
    }
}
function kullanicikontrol()
{
    if ($_SESSION || $_SESSION['yetki'] == '1'||$_SESSION['yetki'] == '2'||$_SESSION['yetki'] == '3') {
    }
    else
    {
        header("Location:giris.php");

    }
}*/
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

?>