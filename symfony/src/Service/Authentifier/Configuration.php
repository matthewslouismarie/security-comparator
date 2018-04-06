<?php

namespace App\Service\Authentifier;

use App\Repository\MemberRepository;
use App\Repository\U2fTokenRepository;
use LM\Authentifier\Configuration\IApplicationConfiguration;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;
use Twig_Environment;
use Twig_Function;
use UnexpectedValueException;

/**
 * @todo Should be rename to something like
 * EasyAuthenticationMiddlewareConfiguration.
 */
class Configuration implements IApplicationConfiguration
{
    private $assetPackage;

    private $appId;

    private $container;

    private $memberRepo;

    private $tokenStorage;

    public function __construct(
        Packages $assetPackage,
        ContainerInterface $container,
        TokenStorageInterface $tokenStorage,
        Twig_Environment $twig,
        MemberRepository $memberRepo,
        U2fTokenRepository $u2fTokenRepo)
    {
        $this->appId = $container->getParameter("u2f.app_id");
        $this->assetPackage = $assetPackage;
        $this->container = $container;
        $this->memberRepo = $memberRepo;
        $this->tokenStorage = $tokenStorage;
        $this->u2fTokenRepo = $u2fTokenRepo;
    }

    public function getAssetUri(string $assetId): string
    {
        return $this->assetPackage->getUrl($assetId);
    }

    public function getAppId(): string
    {
        return $this->appId;
    }

    public function getContainer(): PsrContainerInterface
    {
        return $this->container;
    }

    public function getTokenStorage(): TokenStorageInterface
    {
        return $this->tokenStorage;
    }

    public function getU2fRegistrations(string $username): array
    {
        return $this
            ->u2fTokenRepo
            ->getRegistrationsFromUsername($username)
        ;
    }

    public function isExistingMember(string $username): bool
    {
        $nResults = count($this
            ->memberRepo
            ->findBy([
                "username" => $username,
            ]))
        ;
        if (0 === $nResults) {
            return false;
        } elseif (1 === $nResults) {
            return true;
        } else {
            throw new UnexpectedValueException();
        }
    }

    public function save(): void
    {
    }
}