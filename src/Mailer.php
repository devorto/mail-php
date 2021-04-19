<?php

namespace Devorto\MailPhp;

use Devorto\Mail\Attachment;
use Devorto\Mail\Mail;
use Devorto\Mail\Mailer as MailerInterface;
use Devorto\Mail\Recipient;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class Mail
 *
 * @package Devorto\MailPhp
 */
class Mailer implements MailerInterface
{
    /**
     * @var bool
     */
    protected $senderFlag = false;

    /**
     * Mail constructor.
     *
     * @param bool $senderFlag This is only for postfix to set the header from "-f" in mail command.
     */
    public function __construct(bool $senderFlag = false)
    {
        $this->senderFlag = $senderFlag;
    }

    /**
     * @param Mail $mail
     *
     * @return void
     */
    public function send(Mail $mail): void
    {
        if (empty($mail->getTo())) {
            throw new InvalidArgumentException('No "to" address provided.');
        }

        if (empty($mail->getFrom())) {
            throw new InvalidArgumentException('No "from" address provided.');
        }

        if (empty($mail->getSubject())) {
            throw new InvalidArgumentException('No "subject" provided.');
        }

        if (empty($mail->getMessage())) {
            throw new InvalidArgumentException('No "message" provided.');
        }

        $boundary = uniqid('', true);

        $headers[] = 'From: ' . static::renderRecipient($mail->getFrom());

        if (empty($mail->getReplyTo())) {
            $headers[] = 'Reply-To: ' . static::renderRecipient($mail->getFrom());
        } else {
            $headers[] = 'Reply-To: ' . static::renderRecipient($mail->getReplyTo());
        }

        if (!empty($mail->getCc())) {
            $headers[] = 'Cc: ' . implode(', ', array_map([static::class, 'renderRecipient'], $mail->getCc()));
        }

        if (!empty($mail->getBcc())) {
            $headers[] = 'Bcc: ' . implode(', ', array_map([static::class, 'renderRecipient'], $mail->getBcc()));
        }

        $headers[] = 'MIME-Version: 1.0';
        $headers[] = sprintf('Content-Type: multipart/mixed; boundary="%s"', $boundary);

        $content = static::renderMessage($mail->getMessage(), $boundary);
        foreach ($mail->getAttachments() as $attachment) {
            $content .= static::renderAttachment($attachment, $boundary);
        }

        $content .= sprintf("--%s--\r\n", $boundary);

        $result = mail(
            implode(', ', array_map([static::class, 'renderRecipient'], $mail->getTo())),
            $mail->getSubject(),
            $content,
            implode("\r\n", $headers),
            $this->senderFlag ? '-f' . $mail->getFrom()->getEmail() : null
        );

        if (!$result) {
            throw new RuntimeException('Mail not accepted for delivery.');
        }
    }

    /**
     * @param string $message
     * @param string $boundary
     *
     * @return string
     */
    protected static function renderMessage(string $message, string $boundary): string
    {
        $content = sprintf("--%s\r\n", $boundary);
        $content .= "Content-Type: text/html; charset=\"UTF-8\"\r\n";
        $content .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $content .= chunk_split(base64_encode($message), 60, "\r\n");

        return $content;
    }

    /**
     * @param Attachment $attachment
     * @param string $boundary
     *
     * @return string
     */
    protected static function renderAttachment(Attachment $attachment, string $boundary): string
    {
        $content = sprintf("--%s\r\n", $boundary);
        $content .= sprintf("Content-Type: %s; name=\"%s\"\r\n", $attachment->getMimeType(), $attachment->getName());
        $content .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $content .= chunk_split(base64_encode($attachment->getContent()), 60, "\r\n");

        return $content;
    }

    /**
     * @param Recipient $recipient
     *
     * @return string
     */
    protected static function renderRecipient(Recipient $recipient): string
    {
        if (empty($recipient->getName())) {
            return $recipient->getEmail();
        }

        return sprintf('%s <%s>', $recipient->getName(), $recipient->getEmail());
    }
}
