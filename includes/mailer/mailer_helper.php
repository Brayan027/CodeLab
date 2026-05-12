<?php
// SMTP simple sin dependencias externas (vía SSL/465)
function sendEmail($to, $subject, $body) {
    $host = 'ssl://smtp.gmail.com';
    $port = 465;
    $user = 'soportetecnicosupertiendalaave@gmail.com';
    $pass = 'waso pdmb rvfa iayt';
    
    $timeout = 15;
    $socket = fsockopen($host, $port, $errno, $errstr, $timeout);
    
    if (!$socket) {
        error_log("Error SMTP Socket: $errstr ($errno)");
        return false;
    }
    
    function get_response($socket) {
        $res = "";
        while($str = fgets($socket, 515)) {
            $res .= $str;
            if(substr($str, 3, 1) == " ") break;
        }
        return $res;
    }
    
    get_response($socket);
    fwrite($socket, "EHLO localhost\r\n");
    get_response($socket);
    
    fwrite($socket, "AUTH LOGIN\r\n");
    get_response($socket);
    fwrite($socket, base64_encode($user) . "\r\n");
    get_response($socket);
    fwrite($socket, base64_encode($pass) . "\r\n");
    $auth_res = get_response($socket);
    
    if (substr($auth_res, 0, 3) != '235') {
        error_log("SMTP Auth failed: $auth_res");
        return false;
    }
    
    fwrite($socket, "MAIL FROM: <$user>\r\n");
    get_response($socket);
    fwrite($socket, "RCPT TO: <$to>\r\n");
    get_response($socket);
    fwrite($socket, "DATA\r\n");
    get_response($socket);
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "To: $to\r\n";
    $headers .= "From: CodeLab <$user>\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "Date: " . date('r') . "\r\n";
    
    fwrite($socket, $headers . "\r\n" . $body . "\r\n.\r\n");
    get_response($socket);
    
    fwrite($socket, "QUIT\r\n");
    get_response($socket);
    fclose($socket);
    
    return true;
}
?>
