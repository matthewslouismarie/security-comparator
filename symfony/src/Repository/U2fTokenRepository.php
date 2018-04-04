<?php

namespace App\Repository;

use App\Entity\Member;
use App\Entity\U2fToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ObjectManager;
use LM\Authentifier\Enum\Persistence\Operation;
use LM\Authentifier\Model\PersistOperation;
use Firehed\U2F\Registration;
use Symfony\Bridge\Doctrine\RegistryInterface;
use UnexpectedValueException;

class U2fTokenRepository extends ServiceEntityRepository
{
    private $om;

    public function __construct(
        ObjectManager $om,
        RegistryInterface $registry)
    {
        parent::__construct($registry, U2fToken::class);
        $this->om = $om;
    }

    public function getMemberRegistrations(Member $member): array
    {
        $u2f_tokens = $this->getU2fTokens($member);
        $registrations = array();
        foreach ($u2f_tokens as $tkn) {
            $registration = new Registration();
            $registration->setCounter($tkn->getCounter());
            $registration->setAttestationCertificate($tkn->getAttestation());
            $registration->setPublicKey(base64_decode($tkn->getPublicKey()));
            $registration->setKeyHandle(base64_decode($tkn->getKeyHandle()));
            $registrations[$tkn->getId()] = $registration;
        }

        return $registrations;
    }

    public function getExcept(Member $member, array $ids)
    {
        $qb = $this
            ->createQueryBuilder('u2ftoken')
        ;

        return $qb
            ->where('u2ftoken.member = :member_id')
            ->setParameter('member_id', $member->getId())
            ->andWhere($qb->expr()->notIn('u2ftoken.id', $ids))
            ->getQuery()
            ->getResult()
        ;
    }

    public function getU2fTokens(Member $member): array
    {
        return $this->findBy(['member' => $member]);
    }

    public function processPersistOperation(PersistOperation $persistOperation): void
    {
        if ($persistOperation->getType()->is(new Operation(Operation::UPDATE))) {
            $registration = $persistOperation->getObject();
            $tkn = $this
                ->findOneBy([
                    "publicKey" => base64_encode($registration->getPublicKey()),
                ])
            ;
            // if (1 !== count($tkn)) {
            //     throw new UnexpectedValueException();
            // }
            $counter = $registration->getCounter();
            $attestation = base64_encode(
                $registration->getAttestationCertificateBinary()
            );
            $publicKey = base64_encode($registration->getPublicKey());
            $keyHandle = base64_encode($registration->getKeyHandleBinary());
            $newTkn = new U2fToken(
                null,
                $attestation,
                $counter,
                $keyHandle,
                $tkn->getMember(),
                $tkn->getRegistrationDateTime(),
                $publicKey,
                $tkn->getU2fKeyName()
            );
            $this
                ->om
                ->remove($tkn)
            ;
            $this
                ->om
                ->flush()
            ;
            $this
                ->om
                ->persist($newTkn)
            ;
            $this
                ->om
                ->flush()
            ;
        } else {
            throw new Exception("Not supported yet");
        }
    }

    public function removeU2fToken(Member $member, string $u2fTokenSlug): void
    {
        $u2fToken = $this->findOneBy([
            'member' => $member,
            'name' => $u2fTokenSlug,
        ]);
        $this
            ->om
            ->remove($u2fToken)
        ;
        $this
            ->om
            ->flush()
        ;
    }

    /**
     * @todo Delete?
     */
    public function resetCounters()
    {
        $u2fTokens = $this->findAll();
        foreach ($u2fTokens as $u2fToken) {
            $u2fToken->setCounter(0);
        }
        $this
            ->om
            ->flush()
        ;
    }
}
