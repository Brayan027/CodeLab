<?php
$to = "test@example.com";
$subject = "Test Mail";
$message = "This is a test.";
$headers = "From: webmaster@example.com";

if (mail($to, $subject, $message, $headers)) {
    echo "Mail sent successfully";
} else {
    echo "Mail failed";
}
?>
