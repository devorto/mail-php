<?php

namespace devorto\mail\php;

use devorto\mail\MailAddressException;
use devorto\mail\MailAddressInterface;

class MailAddress implements MailAddressInterface
{
    /**
     * @var string
     */
    private $address;

    /**
     * @var null|string
     */
    private $name;

    /**
     * MailAddress constructor.
     *
     * @param string $address
     * @param string|null $name
     *
     * @throws MailAddressException
     */
    public function __construct(string $address, string $name = null)
    {
        if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
            throw new MailAddressException(sprintf('Invalid mail address "%s"', $address));
        }
        $this->address = $address;
        $this->name = empty($name) ? null : $name;
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }
}
