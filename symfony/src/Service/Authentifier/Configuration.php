<?php

namespace App\Service\Authentifier;

use App\Repository\MemberRepository;
use App\Repository\U2fTokenRepository;
use App\Service\AppIdReader;
use LM\Authentifier\Configuration\IApplicationConfiguration;
use LM\Authentifier\Model\IMember;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;
use Twig_Environment;
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

    private $kernel;

    private $memberRepo;

    private $tokenStorage;

    public function __construct(
        AppIdReader $appIdReader,
        Packages $assetPackage,
        ContainerInterface $container,
        KernelInterface $kernel,
        TokenStorageInterface $tokenStorage,
        Twig_Environment $twig,
        MemberRepository $memberRepo,
        U2fTokenRepository $u2fTokenRepo)
    {
        $this->appId = $appIdReader->getAppId();
        $this->assetPackage = $assetPackage;
        $this->container = $container;
        $this->kernel = $kernel;
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

    public function getComposerDir(): string
    {
        return $this->kernel->getProjectDir().'/vendor';
    }

    public function getContainer(): PsrContainerInterface
    {
        return $this->container;
    }

    public function getCustomTwigDir(): ?string
    {
        return null;
    }

    public function getMember(string $username): IMember
    {
        return $this->memberRepo->findOneBy([
            'username' => $username,
        ]);
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
