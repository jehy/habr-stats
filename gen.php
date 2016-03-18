<?
$fp = fopen('topics.txt', 'w');
for ($i = 278963; $i > 0; $i--) {
    $url = "https://habrahabr.ru/post/" . $i . "/";
    fwrite($fp, $url . "\n");
}
fclose($fp);
?>