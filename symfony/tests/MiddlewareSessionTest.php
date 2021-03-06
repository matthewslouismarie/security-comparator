<?php

declare(strict_types=1);

namespace App\Tests;

use LM\AuthAbstractor\Model\AuthenticationProcess;
use LM\Common\DataStructure\TypedMap;
use LM\Common\Enum\Scalar;
use LM\Common\Model\ArrayObject;

class MiddlewareSessionTest extends TestCaseTemplate
{
    public function testSerialization()
    {
        $session = $this->getSecureSession();

        $authentifiers = [
            ExistingUsernameChallenge::class,
            U2fChallenge::class,
        ];
        $typedMap = new TypedMap([
            'challenges' => new ArrayObject($authentifiers, Scalar::_STR),
        ]);
        $authenticationProcess = new AuthenticationProcess($typedMap);
        $challenges = $authenticationProcess->getChallenges();
        $challenges->setToNextItem();
        $sid = $session->storeObject(
            new AuthenticationProcess($authenticationProcess
                ->getTypedMap()
                ->set('challenges', $challenges, ArrayObject::class)),
            AuthenticationProcess::class
        )
        ;
        $unserializedProcess = $session->getObject($sid, AuthenticationProcess::class);
        $this->assertSame(
            U2fChallenge::class,
            $unserializedProcess->getCurrentChallenge()
        );
    }
}
