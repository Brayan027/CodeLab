<?php
$base = 'PLACEHOLDER_KEY_%s_%s_%s';
$char1 = ['I', 'l', '1', 'i'];
$char2 = ['i', 'l', 'I', '1'];
$char3 = ['I', 'l', 'i', '1'];

$valid_keys = [];

foreach ($char1 as $c1) {
    foreach ($char2 as $c2) {
        foreach ($char3 as $c3) {
            $key = sprintf($base, $c1, $c2, $c3);
            $url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $key;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($httpCode == 200 || $httpCode == 429) {
                echo "FOUND VALID KEY: $key (HTTP $httpCode)\n";
                $valid_keys[] = $key;
            }
        }
    }
}
echo "Done testing " . (count($char1)*count($char2)*count($char3)) . " combinations.\n";
