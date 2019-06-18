<?php

namespace devorto\mail\php;

use devorto\mail\MailMessageException;
use devorto\mail\MailMessageInterface;

class MailMessage implements MailMessageInterface
{
    /**
     * @var string
     */
    private $content;

    /**
     * @var string
     */
    private $mimeType;

    /**
     * MailMessage constructor.
     *
     * @param string $content
     * @param string $mimeType
     *
     * @throws MailMessageException
     */
    public function __construct(string $content, string $mimeType = 'text/plain')
    {
        if (empty($content)) {
            throw new MailMessageException('Message content can not be empty');
        }

        if ($mimeType !== 'text/plain' && $mimeType !== 'text/html') {
            throw new MailMessageException('Message MimeType can only be of type text/plain or text/html');
        }

        $this->content = $content;
        $this->mimeType = $mimeType;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return string
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }
}
