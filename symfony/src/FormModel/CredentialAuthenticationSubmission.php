<?php

namespace App\FormModel;

use App\Validator\Constraints\ValidCredential;

/**
 * @ValidCredential
 */
class CredentialAuthenticationSubmission implements ISubmission
{
    private $username;

    private $password;

    public function __construct(
        ?string $username = null,
        ?string $password = null)
    {
        $this->username = $username;
        $this->password = $password;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function serialize(): string
    {
        return serialize([
            $this->username,
            $this->password,
        ]);
    }

    public function unserialize($serialized): void
    {
        list(
            $this->username,
            $this->password) = unserialize($serialized);
    }
}