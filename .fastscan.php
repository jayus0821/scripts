<?php

$ch = curl_init($_GET['u']);
error_reporting(0);
curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch,CURLOPT_TIMEOUT,1);

curl_exec($ch);
$httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
echo($httpCode."<br>");
if(curl_error($ch)) {
    echo(curl_error($ch));
}
curl_close($ch);
fclose($fp);
?>