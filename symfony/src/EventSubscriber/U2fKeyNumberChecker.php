<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Controller\U2fDeviceRegistrationController;
use App\Entity\U2fToken;
use App\Enum\Setting;
use App\Service\AppConfigManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig_Environment;

class U2fKeyNumberChecker implements EventSubscriberInterface
{
    private $authorizationChecker;

    private $config;

    private $em;

    private $router;

    private $token;

    private $tokenS;

    private $twig;

    public function __construct(
        AppConfigManager $config,
        EntityManagerInterface $em,
        RouterInterface $router,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenS,
        Twig_Environment $twig
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->config = $config;
        $this->em = $em;
        $this->router = $router;
        $this->token = $tokenS->getToken();
        $this->twig = $twig;
        $this->tokenS = $tokenS;
    }

    /**
     * @todo Why is findBy(['member' => $this->token->getUser()]) not working?
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();

        if (!is_array($controller)) {
            return;
        }

        if (null !== $this->tokenS->getToken() && null !== $this->token &&
            $this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') &&
            !is_a($controller[0], U2fDeviceRegistrationController::class)) {
            $nU2fTokens = count($this
                ->em
                ->getRepository(U2fToken::class)
                ->findByUsername($this->token->getUser()->getUsername()))
            ;
            $nU2fTokensRequired = $this
                ->config
                ->getIntSetting(Setting::N_U2F_KEYS_POST_AUTH)
            ;
            // echo("\n  Requis: {$nU2fTokensRequired} contre ".count($u2fTokens)."\n");
            if ($nU2fTokens < $nU2fTokensRequired) {
                $event->setController(function () use ($nU2fTokens, $nU2fTokensRequired) {
                    return new Response($this->twig->render('new_u2f_key_needed.html.twig', [
                        'nU2fTokens' => $nU2fTokens,
                        'nU2fTokensRequired' => $nU2fTokensRequired,
                    ]));
                });
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER => 'onKernelController',
        );
    }
}
