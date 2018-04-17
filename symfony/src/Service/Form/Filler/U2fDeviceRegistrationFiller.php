<?php

namespace App\Service\Form\Filler;

use App\DataStructure\TransitingDataManager;
use App\Exception\NonexistentNodeException;
use App\Model\TransitingData;
use App\Service\Mocker\U2fRegistrationMocker;
use App\Service\SecureSession;
use LM\Authentifier\Model\AuthenticationProcess;
use LM\Authentifier\Model\U2fRegistrationRequest;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

class U2fDeviceRegistrationFiller
{
    const FORM_NAME = 'form';

    private $mocker;

    private $secureSession;

    public function __construct(
        U2fRegistrationMocker $mocker,
        SecureSession $secureSession
    ) {
        $this->mocker = $mocker;
        $this->secureSession = $secureSession;
    }

    public function fillForm(Crawler $crawler, string $sid): Form
    {
        $cycle = $this->mocker->getNewCycle();
        $map = $this
            ->secureSession
            ->getObject($sid, AuthenticationProcess::class)
            ->getTypedMap()
            ->set(
                'current_u2f_registration_request',
                new U2fRegistrationRequest($cycle->getRequest(), null),
                U2fRegistrationRequest::class
            )
        ;
        $this
            ->secureSession
            ->setObject(
                $sid,
                new AuthenticationProcess($map),
                AuthenticationProcess::class
            )
        ;

        $formNode = $crawler->filter('[name="'.self::FORM_NAME.'"]');
        if (0 === $formNode->count()) {
            throw new NonexistentNodeException();
        }
        $form = $formNode->form([
            self::FORM_NAME.'[u2fDeviceResponse]' => $cycle->getResponse(),
        ]);

        return $form;
    }
}
