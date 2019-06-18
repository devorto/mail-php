<?php

namespace devorto\mail\php;

use devorto\mail\MailAddressInterface;
use devorto\mail\MailAttachmentInterface;
use devorto\mail\MailException;
use devorto\mail\MailInterface;
use devorto\mail\MailMessageInterface;
use devorto\mail\MailSubjectInterface;

class Mail implements MailInterface
{
    /**
     * @var MailAddressInterface
     */
    protected $from = null;

    /**
     * @var MailAddressInterface
     */
	protected $to = null;

    /**
     * @var MailAddressInterface
     */
	protected $replyTo = null;

    /**
     * @var MailAddressInterface
     */
    protected $cc = null;

    /**
     * @var MailAttachmentInterface[]
     */
	protected $attachments = [];

    /**
     * @var MailMessageInterface;
     */
	protected $htmlMessage = null;

	/**
	 * @var MailMessageInterface
	 */
	protected $textMessage = null;

    /**
     * @var MailSubjectInterface
     */
	protected $subject;

    public function setFrom(MailAddressInterface $from): MailInterface
    {
        $this->from = $from;

        return $this;
    }

    public function getFrom(): MailAddressInterface
    {
        return $this->from;
    }

    public function setTo(MailAddressInterface $to): MailInterface
    {
        $this->to = $to;

        return $this;
    }

    public function getTo(): MailAddressInterface
    {
        return $this->to;
    }

    public function setReplyTo(MailAddressInterface $replyTo): MailInterface
    {
        $this->replyTo = $replyTo;

        return $this;
    }

    /**
     * @return MailAddressInterface
     */
    public function getCc(): MailAddressInterface
    {
        return $this->cc;
    }

    /**
     * @param MailAddressInterface $cc
     *
     * @return MailInterface
     */
    public function setCc(MailAddressInterface $cc): MailInterface
    {
        $this->cc = $cc;

        return $this;
    }

    public function getSubject(): MailSubjectInterface
    {
        return $this->subject;
    }

    public function addAttachment(MailAttachmentInterface $attachment): MailInterface
    {
        $this->attachments[] = $attachment;

        return $this;
    }

    /**
     * @return MailAttachmentInterface[]
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

	/**
	 * @param MailMessageInterface $message
	 *
	 * @return MailInterface
	 * @throws MailException
	 */
    public function setHtmlMessage(MailMessageInterface $message): MailInterface
    {
    	if($message->getMimeType() !== 'text/html') {
			throw new MailException('Invalid mimetype set for html message');
		}

    	$this->htmlMessage = $message;

        return $this;
    }

    public function getHtmlMessage(): MailMessageInterface
    {
        return $this->htmlMessage;
    }

	/**
	 * @param MailMessageInterface $message
	 *
	 * @return MailInterface
	 * @throws MailException
	 */
    public function setTextMessage(MailMessageInterface $message): MailInterface
	{
		if($message->getMimeType() !== 'text/plain') {
			throw new MailException('Invalid mimetype set for text message');
		}

		$this->textMessage = $message;

		return $this;
	}

	public function getTextMessage(): MailMessageInterface
	{
		return $this->textMessage;
	}

	/**
     * @return bool
     * @throws MailException
     */
    public function send(): bool
    {
        if($this->to === null) {
            throw new MailException('We have no idea where "to" send the mail');
        }

        if($this->from === null) {
			throw new MailException('We have no idea where the mail came from');
		}

        if($this->subject === null) {
            throw new MailException('What "subject" should the mail have?');
        }

        if(empty($this->textMessage) && empty($this->htmlMessage)) {
            throw new MailException('We have no "message" to send');
        }

		if($this->to->getName() === null) {
			$to = $this->to->getAddress();
		} else {
			$to = sprintf('%s <%s>', $this->to->getName(), $this->to->getAddress());
		}

        $boundary = uniqid('', true);

        if($this->from->getName() === null) {
            $headers[] = sprintf('From: %s', $this->from->getAddress());
        } else {
            $headers[] = sprintf('From: %s <%s>', $this->from->getName(), $this->from->getAddress());
        }

        if($this->replyTo === null) {
			if($this->from->getName() === null) {
				$headers[] = sprintf('Reply-To: %s', $this->from->getAddress());
			} else {
				$headers[] = sprintf('Reply-To: %s <%s>', $this->from->getName(), $this->from->getAddress());
			}
		} else {
			if($this->replyTo->getName() === null) {
				$headers[] = sprintf('Reply-To: %s', $this->replyTo->getAddress());
			} else {
				$headers[] = sprintf('Reply-To: %s <%s>', $this->replyTo->getName(), $this->replyTo->getAddress());
			}
		}

        if ($this->cc !== null) {
            if ($this->cc->getName() === null) {
                $headers[] = sprintf('Cc: %s', $this->cc->getAddress());
            } else {
                $headers[] = sprintf('Cc: %s <%s>', $this->cc->getName(), $this->cc->getAddress());
            }
        }

        $headers[] = 'MIME-Version: 1.0';
        $headers[] = sprintf('Content-Type: multipart/mixed; boundary="%s"', $boundary);

        $content = '';

        if(isset($this->textMessage)) {
			$content .= static::renderMessage($this->textMessage, $boundary);
		}

		if(isset($this->htmlMessage)) {
			$content .= static::renderMessage($this->htmlMessage, $boundary);
		}

        foreach ($this->attachments as $attachment) {
            $content .= static::renderAttachment($attachment, $boundary);
        }

        $content .= sprintf("--%s--\r\n", $boundary);

        return mail(
            $to,
            $this->subject->getSubject(),
            $content,
            implode("\r\n", $headers)
        );
    }

    protected static function renderMessage(MailMessageInterface $message, string $boundary): string
	{
		$content = sprintf("--%s\r\n", $boundary);
		$content .= sprintf("Content-Type: %s; charset=\"UTF-8\"\r\n", $message->getMimeType());
		$content .= "Content-Transfer-Encoding: base64\r\n\r\n";
		$content .= chunk_split(base64_encode($message->getContent()), 60, "\r\n");

		return $content;
	}

	protected static function renderAttachment(MailAttachmentInterface $attachment, string $boundary): string
	{
		$content = sprintf("--%s\r\n", $boundary);
		$content .= sprintf("Content-Type: %s; name=\"%s\"\r\n", $attachment->getMimeType(), $attachment->getName());
		$content .= "Content-Transfer-Encoding: base64\r\n\r\n";
		$content .= chunk_split(base64_encode($attachment->getContent()), 60, "\r\n");

		return $content;
	}
}
