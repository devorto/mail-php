<?php

namespace devorto\mail\php;

use devorto\mail\MailAttachmentException;
use devorto\mail\MailAttachmentInterface;
use function realpath;
use function sprintf;

class MailAttachment implements MailAttachmentInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $content;

    /**
     * @var string
     */
    private $mimeType;

    public function __construct(string $name, string $content, string $mimeType = 'text/plain')
    {
    	if(empty($name)) {
			throw new MailAttachmentException('Variable $name cannot be empty');
		}
		if(empty($content)) {
			throw new MailAttachmentException('Variable $content cannot be empty');
		}
		if(empty($mimeType)) {
			throw new MailAttachmentException('Variable $mimeType cannot be empty');
		}

        $this->name = $name;
        $this->content = $content;
        $this->mimeType = $mimeType;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     * @throws MailAttachmentException
     */
    public function getContent(): string
    {
        if(empty($this->content)) {
            throw new MailAttachmentException('Cannot get non-existing content');
        }

        return $this->content;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

	/**
	 * @param string $path
	 *
	 * @return MailAttachmentInterface
	 * @throws MailAttachmentException
	 */
    public function setContentFromFile(string $path): MailAttachmentInterface
    {
    	if(empty($path)) {
			throw new MailAttachmentException('Variable $path cannot be empty');
		}
		if(!($path = realpath($path))) {
			throw new MailAttachmentException(sprintf('File "%s" cannot be found', $path));
		}

        return $this->setContent(
            file_get_contents($path)
        );
    }

    public function setContent(string $content): MailAttachmentInterface
    {
		if(empty($content)) {
			throw new MailAttachmentException('Variable $content cannot be empty');
		}

        $this->content = $content;

        return $this;
    }
}
