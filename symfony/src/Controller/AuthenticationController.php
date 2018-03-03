<?php

namespace App\Controller;

use App\DataStructure\TransitingDataManager;
use App\Exception\AccessDeniedException;
use App\Form\LoginRequestType;
use App\FormModel\CredentialAuthenticationSubmission;
use App\FormModel\LoginRequest;
use App\Form\UserConfirmationType;
use App\Model\AuthorizationRequest;
use App\Model\GrantedAuthorization;
use App\Model\TransitingData;
use App\Model\ArrayObject;
use App\Service\SecureSession;
use App\Service\SerializableStack;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @todo Test denied authorizations. (what happens if the user accesses
 * finalize_login without the authorization?)
 */
class AuthenticationController extends AbstractController
{
    /**
     * @Route(
     *  "/not-authenticated/start-login",
     *  name="login_request",
     *  methods={"GET"})
     */
    public function startLogin(
        Request $request,
        SerializableStack $SerializableStack,
        SecureSession $sSession)
    {
        $loginRequest = new AuthorizationRequest(false, 'finalize_login', null);
        $sid = $SerializableStack->create($loginRequest);
        $url = $this->generateUrl('medium_security_credential', array(
            'SerializableStackSid' => $sid,
        ));

        return new RedirectResponse($url);
    }


    /**
     * @Route(
     *  "/not-authenticated/authenticate",
     *  name="authenticate",
     *  methods={"GET"})
     */
    public function authenticate(
        Request $request,
        SerializableStack $SerializableStack,
        SecureSession $secureSession)
    {
        $tdm = (new TransitingDataManager())
            ->add(new TransitingData('checkers', 'initial_route', new ArrayObject(['ic_username', 'ic_u2f', 'authentication_processing'])))
        ;
        $sid = $secureSession->storeObject($tdm, TransitingDataManager::class);

        return new RedirectResponse($this->generateUrl('ic_initialization', [
            'sid' => $sid,
        ]));
    }

    /**
     * @todo Have a better error handling.
     *
     * @Route(
     *  "/not-authenticated/finalise-login/{SerializableStackSid}",
     *  name="finalize_login",
     *  methods={"GET", "POST"})
     */
    public function finishLogin(
        Request $request,
        SecureSession $sSession,
        SerializableStack $SerializableStack,
        string $SerializableStackSid)
    {
        $credential = $SerializableStack->get(
            $SerializableStackSid,
            1,
            CredentialAuthenticationSubmission::class
        );
        $authorizationRequest = $SerializableStack->peek($SerializableStackSid);
        if (!is_a($authorizationRequest, GrantedAuthorization::class)) {
            throw new AccessDeniedException();
        }

        $loginRequest = new LoginRequest($credential->getUsername());
        $form = $this->createForm(LoginRequestType::class, $loginRequest);
        $form->handleRequest($request);

        return $this->render('finish_login.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @todo Remove (seems useless)
     *
     * @Route(
     *  "/not-authenticated/process-login/{sid}",
     *  name="authentication_processing")
     */
    public function processAuthentication()
    {
    }

    /**
     * @todo /all/ is temporary.
     * @Route(
     *  "/authenticated/successful-login",
     *  name="successful_authentication")
     */
    public function successfulAuthentication()
    {
        return $this->render('successful_authentication.html.twig');
    }

    /**
     * @Route(
     *  "/authenticated/log-out",
     *  name="logout",
     *  methods={"GET", "POST"})
     */
    public function logout(Request $request)
    {
        $form = $this->createForm(UserConfirmationType::class);
        $form->handleRequest($request);

        return $this->render('registration/logout.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route(
     *  "/authenticated/not-logged-out",
     *  name="not_logged_out",
     *  methods={"GET"})
     */
    public function notLoggedOut()
    {
        return $this->render('not_logged_out_error.html.twig');
    }
}
