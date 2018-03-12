<?php

namespace App\Service;

use App\DataStructure\TransitingDataManager;
use App\Exception\IdentityChecker\InvalidCheckerException;
use App\Exception\IdentityChecker\StartedIdentityCheckException;
use App\Model\ArrayObject;
use App\Model\IdentityVerificationRequest;
use App\Model\Integer;
use App\Model\StringObject;
use App\Model\TransitingData;
use App\Service\SecureSession;
use App\Repository\U2fTokenRepository;
use Exception;
use UnexpectedValueException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @todo Rename to IdentityRequestManager?
 * @todo Magic string.
 * @todo Method for checking sid?
 */
class IdentityVerificationRequestManager
{
    private $router;

    private $secureSession;

    private $tokenStorage;

    public function __construct(
        RouterInterface $router,
        SecureSession $secureSession,
        TokenStorageInterface $tokenStorage)
    {
        $this->router = $router;
        $this->secureSession = $secureSession;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @todo Check that $routeName is a valid route and that $checkers is a
     * valid array of route names (string + existing route)?
     */
    public function create(
        string $routeName,
        array $checkers,
        array $additionalData = []): IdentityVerificationRequest
    {
        $tdm = $this->createTdm($additionalData, $checkers, $routeName);
        $sid = $this
            ->secureSession
            ->storeObject($tdm, TransitingDataManager::class)
        ;
        $url = $this
            ->router
            ->generate('ic_initialization', [
                'sid' => $sid,
            ])
        ;

        return new IdentityVerificationRequest($sid, $url);
    }

    private function createTdm(
        array $additionalData,
        array $checkers,
        string $routeName)
    {
        if (false === in_array('ic_username', $checkers, true) &&
            false === in_array('ic_credential', $checkers, true)) {
            return $this
                ->initializeDefaultTdm($additionalData, $checkers, $routeName)
                ->add(new TransitingData(
                    'username',
                    $routeName,
                    new StringObject($this
                        ->tokenStorage
                        ->getToken()
                        ->getUser()
                        ->getUsername())))
            ;
        } else {
            return $this->initializeDefaultTdm($additionalData, $checkers, $routeName);
        }
    }

    public function getAdditionalData(string $sid): array
    {
        $tdm = $this
            ->secureSession
            ->getObject($sid, TransitingDataManager::class)
        ;
        return $tdm
            ->getBy('key', 'additional_data')
            ->getOnlyValue()
            ->getValue()
            ->toArray()
        ;
    }

    /**
     * @todo Use a more specific exception.
     */
    public function checkIdentityFromSid(string $sid): void
    {
        $tdm = $this
            ->secureSession
            ->getObject($sid, TransitingDataManager::class)
        ;
        $identityVerified = $this->isIdentityCheckedFromObject($tdm);
        if (false === $identityVerified) {
            throw new Exception();
        }
    }

    public function isIdentityCheckedFromObject(TransitingDataManager $tdm): bool
    {
        try {
            $checkers = $tdm
                ->getBy('key', 'checkers')
                ->getOnlyValue()
                ->getValue(ArrayObject::class)
                ->toArray()
            ;
        }
        catch (UnexpectedValueException $e) {
            return false;
        }
        foreach ($checkers as $checker)
        {
            try {
                $valids = $tdm
                    ->getBy('route', $checker)
                    ->getBy('key', 'successful_authentication')
                    ->toArray()
                ;
            } catch (UnexpectedValueException $e) {
               return false;
            }
            foreach ($valids as $valid) {
                if (true !== $valid->toBoolean()) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @todo Use a more specific exception.
     */
    public function checkNotStarted(string $sid): void
    {
        $currentCheckerIndexTdm = $this
            ->secureSession
            ->getObject($sid, TransitingDataManager::class)
            ->getBy('key', 'current_checker_index')
        ;
        if (0 !== $currentCheckerIndexTdm->getSize()) {
            throw new StartedIdentityCheckException();
        }
    }

    public function verifyRoute(string $routeName, string $sid): int
    {
        $tdm = $this
            ->secureSession
            ->getObject($sid, TransitingDataManager::class)
        ;
        $checkerIndex = $tdm
            ->getBy('key', 'current_checker_index')
            ->getOnlyValue()
            ->getValue(Integer::class)
            ->toInteger()
        ;
        $checkers = $tdm
            ->getBy('key', 'checkers')
            ->getOnlyValue()
            ->getValue(ArrayObject::class)
            ->toArray()
        ;
        if ($routeName !== $checkers[$checkerIndex]) {
            throw new InvalidCheckerException();
        }

        return $checkerIndex;
    }

    private function initializeDefaultTdm(
        array $additionalData,
        array $checkers,
        string $routeName)
    {
        return (new TransitingDataManager())
            ->add(new TransitingData(
                'checkers',
                $routeName,
                new ArrayObject($checkers)))
            ->add(new TransitingData(
                'additional_data',
                $routeName,
                new ArrayObject($additionalData)))
        ;
    }
}
