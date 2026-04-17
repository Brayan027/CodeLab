<?php
$apiKey = 'AIzaSyCC7EcjCGeHwYSFt0B4Y9IMg_oHe6nTSVU';
$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

file_put_contents('ai_log.txt', "HTTP: $httpCode\n$response");
