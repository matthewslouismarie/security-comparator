<?php

namespace App\Controller;

use App\Callback\Authentifier\PasswordUpdateCallback;
use App\Callback\Authentifier\AccountDeletionCallback;
use App\Enum\Setting;
use App\Form\PasswordUpdateType;
use App\Form\UserConfirmationType;
use App\FormModel\PasswordUpdateSubmission;
use App\Service\AppConfigManager;
use App\Service\Authentifier\MiddlewareDecorator;
use App\Service\ChallengeSpecification;
use LM\Authentifier\Challenge\CredentialChallenge;
use LM\Common\Model\ArrayObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @todo Add Controller suffix.
 */
class MemberAccount extends AbstractController
{
    /**
     * @Route(
     *  "/authenticated/my-account",
     *  name="member_account"
     * )
     */
    public function memberAccount(AppConfigManager $config)
    {
        $allowMemberToManageU2fKeys = $config->getBoolSetting(Setting::ALLOW_MEMBER_TO_MANAGE_U2F_KEYS);

        return $this->render('member_account.html.twig', [
            'allow_member_to_manage_u2f_keys' => $allowMemberToManageU2fKeys,            
        ]);
    }

    /**
     * @Route(
     *  "/authenticated/change-password/{sid}",
     *  name="change_password")
     */
    public function updatePassword(
        string $sid = null,
        ChallengeSpecification $challengeSpecification,
        Request $httpRequest,
        MiddlewareDecorator $decorator)
    {
        if (null === $sid) {
            $submission = new PasswordUpdateSubmission();
            $form = $this->createForm(PasswordUpdateType::class, $submission);
            $form->handleRequest($httpRequest);
            if ($form->isSubmitted() && $form->isValid()) {
                $callback = new PasswordUpdateCallback($submission->getPassword());

                return $decorator->createProcess(
                    $callback,
                    $httpRequest->get('_route'),
                    $challengeSpecification->getChallenges($this->getUser()->getUsername()),
                    $this->getUser()->getUsername())
                ;
            }

            return $this->render('change_password.html.twig', [
                'form' => $form->createView(),
            ]);
        } else {
            return $decorator->updateProcess($httpRequest, $sid);
        }
    }

    /**
     * @Route(
     *  "/authenticated/my-account/delete-account/{sid}",
     *  name="delete_account")
     */
    public function deleteAccount(
        string $sid = null,
        Request $httpRequest,
        MiddlewareDecorator $decorator)
    {
        if (null === $sid) {
            $form = $this->createForm(UserConfirmationType::class);

            $form->handleRequest($httpRequest);
            if ($form->isSubmitted() && $form->isValid()) {
                $callback = new AccountDeletionCallback($this->getUser());

                return $decorator->createProcess(
                    $callback,
                    $httpRequest->get('_route'),
                    new ArrayObject([
                        CredentialChallenge::class,
                    ], 'string'));
            }

            return $this->render('delete_account.html.twig', [
                'form' => $form->createView(),
            ]);
        } else {
            return $decorator->updateProcess($httpRequest, $sid);            
        }
    }
}
