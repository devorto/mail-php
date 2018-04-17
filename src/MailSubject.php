<?php

namespace devorto\mail\php;

use devorto\mail\MailSubjectException;
use devorto\mail\MailSubjectInterface;

class MailSubject implements MailSubjectInterface
{
    /**
     * @var string
     */
    private $subject;

    /**
     * MailSubject constructor.
     * @param string $subject
     * @throws MailSubjectException
     */
    public function __construct(string $subject)
    {
        if(empty($subject)) {
            throw new MailSubjectException('Variable $subject cannot be empty');
        }

        $this->subject = $subject;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }
}
