<?php

namespace App\Controller\IdentityChecker;

use App\DataStructure\TransitingDataManager;
use App\Service\SecureSession;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;

class MasterChecker extends AbstractController
{
    /**
     * @todo type check for $checkers
     *
     * @Route(
     *  "/all/initiate-identity-check/{sid}",
     *  name="ic_initialization")
     */
    public function initiateIdentityCheck(
            string $sid,
            SecureSession $secureSession)
    {
        $tdm = $secureSession->getObject($sid, TransitingDataManager::class);
        $checkers = $tdm
            ->getBy('key', 'checkers')
            ->getOnlyValue()
            ->getValue()
            ->toArray()
        ;

        return new RedirectResponse($this->generateUrl($checkers[0], [
            'sid' => $sid,
        ]));
    }
}