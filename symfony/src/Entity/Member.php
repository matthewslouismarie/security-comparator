<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MemberRepository")
 */
class Member implements UserInterface, \Serializable
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @todo hash password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\Column(length=25, unique=true, nullable=false)
     */
    private $username;

    public function __construct(string $username)
    {
        $this->username = $username;
    }

    public function eraseCredentials()
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getRoles()
    {
        return array('ROLE_USER');
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getSalt()
    {
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setPassword(string $password)
    {
        $this->password = $password;
    }

    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->password,
            $this->username,
        ));
    }

    public function unserialize($data)
    {
        list(
            $this->id,
            $this->username,
            $this->password,
            // see section on salt below
            // $this->salt
        ) = unserialize($data);
    }
}