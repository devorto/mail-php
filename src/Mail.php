<?php

namespace Devorto\MailPhp;

use Devorto\Mail\Attachment;
use Devorto\Mail\Mail as MailInterface;
use Devorto\Mail\Recipient;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class Mail
 *
 * @package Devorto\MailPhp
 */
class Mail implements MailInterface
{
    /**
     * @var array
     */
    protected $to = [];

    /**
     * @var array
     */
    protected $cc = [];

    /**
     * @var array
     */
    protected $bcc = [];

    /**
     * @var Recipient
     */
    protected $from;

    /**
     * @var Recipient
     */
    protected $replyTo;

    /**
     * @var bool
     */
    protected $senderFlag = false;

    /**
     * @var string
     */
    protected $subject;

    /**
     * @var Attachment[]
     */
    protected $attachments = [];

    /**
     * @var string
     */
    protected $message;

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
     * @param Recipient $recipient
     *
     * @return MailInterface
     */
    public function addTo(Recipient $recipient): MailInterface
    {
        $this->to[] = $recipient;

        return $this;
    }

    /**
     * @param Recipient $recipient
     *
     * @return MailInterface
     */
    public function addCc(Recipient $recipient): MailInterface
    {
        $this->cc[] = $recipient;

        return $this;
    }

    /**
     * @param Recipient $recipient
     *
     * @return MailInterface
     */
    public function addBcc(Recipient $recipient): MailInterface
    {
        $this->bcc[] = $recipient;

        return $this;
    }

    /**
     * @param Recipient $recipient
     *
     * @return MailInterface
     */
    public function setFrom(Recipient $recipient): MailInterface
    {
        $this->from = $recipient;

        return $this;
    }

    /**
     * @param Recipient $recipient
     *
     * @return MailInterface
     */
    public function setReplyTo(Recipient $recipient): MailInterface
    {
        $this->replyTo = $recipient;

        return $this;
    }

    /**
     * @param string $subject
     *
     * @return MailInterface
     */
    public function setSubject(string $subject): MailInterface
    {
        $subject = trim($subject);
        if (empty($subject)) {
            throw new InvalidArgumentException('Subject cannot be empty.');
        }

        $this->subject = $subject;

        return $this;
    }

    /**
     * @param Attachment $attachment
     *
     * @return MailInterface
     */
    public function addAttachment(Attachment $attachment): MailInterface
    {
        $this->attachments[] = $attachment;

        return $this;
    }

    /**
     * @param string $message
     *
     * @return MailInterface
     */
    public function setMessage(string $message): MailInterface
    {
        $message = trim($message);
        if (empty($message)) {
            throw new InvalidArgumentException('Message cannot be empty.');
        }

        $this->message = $message;

        return $this;
    }

    /**
     * @return MailInterface
     */
    public function send(): MailInterface
    {
        if (empty($this->to)) {
            throw new RuntimeException('No "to" address provided.');
        }

        if (empty($this->from)) {
            throw new RuntimeException('No "from" address provided.');
        }

        if (empty($this->subject)) {
            throw new RuntimeException('No "subject" provided.');
        }

        if (empty($this->message)) {
            throw new RuntimeException('No "message" provided.');
        }

        $boundary = uniqid('', true);

        $headers[] = 'From: ' . static::renderRecipient($this->from);

        if (empty($this->replyTo)) {
            $headers[] = 'Reply-To: ' . static::renderRecipient($this->from);
        } else {
            $headers[] = 'Reply-To: ' . static::renderRecipient($this->replyTo);
        }

        if (!empty($this->cc)) {
            $headers[] = 'Cc: ' . implode(', ', array_map([static::class, 'renderRecipient'], $this->cc));
        }

        if (!empty($this->bcc)) {
            $headers[] = 'Bcc: ' . implode(', ', array_map([static::class, 'renderRecipient'], $this->bcc));
        }

        $headers[] = 'MIME-Version: 1.0';
        $headers[] = sprintf('Content-Type: multipart/mixed; boundary="%s"', $boundary);

        $content = static::renderMessage($this->message, $boundary);

        foreach ($this->attachments as $attachment) {
            $content .= static::renderAttachment($attachment, $boundary);
        }

        $content .= sprintf("--%s--\r\n", $boundary);

        $result = mail(
            implode(', ', array_map([static::class, 'renderRecipient'], $this->to)),
            $this->subject,
            $content,
            implode("\r\n", $headers),
            $this->senderFlag ? '-f' . $this->from->getEmail() : null
        );

        // Cleanup internal data after sending, because this class me be re-used.
        $this->to = [];
        $this->cc = [];
        $this->bcc = [];
        $this->from = null;
        $this->replyTo = null;
        $this->subject = null;
        $this->message = null;
        $this->attachments = [];

        if (!$result) {
            throw new RuntimeException('Mail not accepted for delivery.');
        }

        return $this;
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
