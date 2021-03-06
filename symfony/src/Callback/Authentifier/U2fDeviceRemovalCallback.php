<?php

declare(strict_types=1);

namespace App\Callback\Authentifier;

use App\Entity\U2fToken;
use Doctrine\ORM\EntityManagerInterface;
use LM\AuthAbstractor\Model\IAuthenticationProcess;
use LM\AuthAbstractor\Model\IAuthenticationCallback;
use Psr\Http\Message\ResponseInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Response;
use Twig_Environment;

class U2fDeviceRemovalCallback implements IAuthenticationCallback
{
    private $failureClosure;

    private $manager;

    private $psr7Factory;

    private $twig;

    private $u2fRegistration;

    public function __construct(
        FailureClosure $failureClosure,
        EntityManagerInterface $manager,
        Twig_Environment $twig
    ) {
        $this->failureClosure = $failureClosure->getClosure();
        $this->manager = $manager;
        $this->psr7Factory = new DiactorosFactory();
        $this->twig = $twig;
    }

    public function handleFailedProcess(IAuthenticationProcess $authProcess): ResponseInterface
    {
        return ($this->failureClosure)($authProcess);
    }

    public function handleSuccessfulProcess(IAuthenticationProcess $authProcess): ResponseInterface
    {
        $u2fRegistration = $this
            ->manager
            ->merge($this->u2fRegistration)
        ;
        $this
            ->manager
            ->remove($u2fRegistration)
        ;
        $this
            ->manager
            ->flush()
        ;

        $httpResponse = $this
            ->twig
            ->render('messages/success.html.twig', [
                'pageTitle' => 'Successful Removal of U2F Device',
                'message' => 'They U2F Device was successfully removed from your account.',
            ])
        ;

        return $this
            ->psr7Factory
            ->createResponse(new Response($httpResponse))
        ;
    }

    /**
     * @todo Make immutable.
     */
    public function setU2fRegistration(U2fToken $u2fRegistration)
    {
        $this->u2fRegistration = $u2fRegistration;
    }
}
