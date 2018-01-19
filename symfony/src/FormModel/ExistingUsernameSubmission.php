<?php

namespace App\FormModel;

use App\Validator\Constraints\ExistingMemberConstraint;
use Serializable;

class ExistingUsernameSubmission implements Serializable
{
    /**
     * @ExistingMemberConstraint
     */
    private $username;

    public function __construct(?string $username = null)
    {
        $this->username;
    }

    public function setUsername(?string $username)
    {
        $this->username = $username;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function serialize(): string
    {
        return serialize([
            $this->username,
        ]);
    }

    public function unserialize($serialized): void
    {
        list(
            $this->username) = unserialize($serialized);
    }
}