<?php

use Devorto\Mail\Mail;
use Devorto\Mail\Recipient;
use Devorto\MailPhp\Mailer;

require_once __DIR__ . '/../vendor/autoload.php';

$message = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Test mail</title>
</head>
<body>
    This is a test email and you cannot respond to it :)
</body>
</html>
HTML;

$mail = (new Mail())
    ->setTo(new Recipient('info@devorto.com', 'Info'))
    ->setFrom(new Recipient('no-reply@devorto.com', 'NoReply'))
    ->setSubject('Test mail')
    ->setMessage($message);

(new Mailer())
    ->send($mail);
