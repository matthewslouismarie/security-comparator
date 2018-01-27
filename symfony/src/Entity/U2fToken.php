<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\U2fTokenRepository")
 */
class U2fToken
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Member")
     */
    private $member;

    /**
     * @ORM\Column(type="string", length=788)
     */
    private $attestation;

    /**
     * @ORM\Column(type="integer")
     */
    private $counter;

    /**
     * @ORM\Column(type="string", length=88)
     */
    private $keyHandle;

    /**
     * @ORM\Column(type="datetimetz_immutable")
     */
    private $registrationDateTime;

    /**
     * @ORM\Column(type="string", length=88)
     */
    private $publicKey;

    /**
     * @ORM\Column()
     */
    private $u2fKeyName;

    public function __construct(
        ?int $id,
        string $attestation,
        int $counter,
        string $keyHandle,
        Member $member,
        \DateTimeImmutable $registrationDateTime,
        string $publicKey,
        string $u2fKeyName)
    {
        $this->id = $id;
        $this->attestation = $attestation;
        $this->counter = $counter;
        $this->keyHandle = $keyHandle;
        $this->member = $member;
        $this->registrationDateTime = $registrationDateTime;
        $this->publicKey = $publicKey;
        $this->u2fKeyName = $u2fKeyName;
    }

    public function getAttestation(): string
    {
        return $this->attestation;
    }

    public function getCounter(): int
    {
        return $this->counter;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKeyHandle(): string
    {
        return $this->keyHandle;
    }

    public function getRegistrationDateTime(): \DateTimeImmutable
    {
        return $this->registrationDateTime;
    }

    public function getMember(): Member
    {
        return $this->member;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function getU2fKeyName(): string
    {
        return $this->u2fKeyName;
    }

    public function setCounter(int $counter): void
    {
        $this->counter = $counter;
    }
}
