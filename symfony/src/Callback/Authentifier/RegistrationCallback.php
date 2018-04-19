<?php

declare(strict_types=1);

namespace App\Callback\Authentifier;

use App\Factory\MemberFactory;
use App\Factory\U2fRegistrationFactory;
use LM\Authentifier\Enum\Persistence\Operation;
use LM\Authentifier\Model\AuthenticationProcess;
use LM\Authentifier\Model\AuthentifierResponse;
use LM\Authentifier\Model\IU2fRegistration;
use Psr\Container\ContainerInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Response;

class RegistrationCallback extends AbstractCallback
{
    public function handleSuccessfulProcess(AuthenticationProcess $authProcess): AuthentifierResponse
    {
        $member = $this
            ->getContainer()
            ->get(MemberFactory::class)
            ->createFrom($authProcess->getMember())
        ;
        $em = $this
            ->getContainer()
            ->get('doctrine')
            ->getManager()
        ;
        $em->persist($member);
        foreach ($authProcess->getPersistOperations() as $operation) {
            if ($operation->getType()->is(new Operation(Operation::CREATE))) {
                $object = $operation->getObject();
                if (is_a($object, IU2fRegistration::class)) {
                    $u2fToken = $this
                        ->getContainer()
                        ->get(U2fRegistrationFactory::class)
                        ->toEntity($object, $member)
                    ;
                    $em->persist($u2fToken);
                }
            }
        }
        $em->flush();
        $psr7Factory = new DiactorosFactory();

        $httpResponse = $this
            ->getContainer()
            ->get('twig')
            ->render('messages/success.html.twig', [
                'pageTitle' => 'Successful account creation',
                'message' => 'Your account was successfully created.',
            ])
        ;

        return new AuthentifierResponse(
            $authProcess,
            $psr7Factory->createResponse(new Response($httpResponse))
        )
        ;
    }

    public function wakeUp(ContainerInterface $container): void
    {
        parent::wakeUp($container);
    }

    public function serialize()
    {
        return serialize([]);
    }

    public function unserialize($serialized)
    {
    }
}
