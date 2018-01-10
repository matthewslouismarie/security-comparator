<?php

namespace App\Tests\Controller;

use App\Entity\U2FToken;
use Firehed\U2F\SignRequest;

abstract class AbstractAccessManagementTestCase extends DbWebTestCase
{
    private $u2fCount = 0;

    public function runLoggedOutTests()
    {
        $this->checkUrlStatusCode(
            '/not-authenticated/authenticate/username-and-password',
            200)
        ;
        $this->checkUrlStatusCode(
            '/not-authenticated/authenticate/u2f-key',
            302)
        ;
        $this->checkUrlStatusCode(
            '/authenticated/change-password',
            302)
        ;
        $this->checkUrlStatusCode(
            '/authenticated/log-out',
            302)
        ;
    }

    public function runLoggedInTests()
    {
        $this->checkUrlStatusCode(
            '/not-authenticated/authenticate/username-and-password',
            302)
        ;
        $this->checkUrlStatusCode(
            '/authenticated/change-password',
            200)
        ;
        $this->checkUrlStatusCode(
            '/authenticated/log-out',
            200)
        ;
    }

    public function logIn(string $username, string $password)
    {
        $upLoginGet = $this
            ->getClient()
            ->request('GET', '/not-authenticated/authenticate/username-and-password')
        ;
        $upButton = $upLoginGet->selectButton('username_and_password[submit]');
        $form = $upButton->form(array(
            'username_and_password[username]' => $username,
            'username_and_password[password]' => $password,
        ));
        $this
            ->getClient()
            ->submit($form)
        ;
        $this
            ->getClient()
            ->followRedirect()
        ;
        $session = $this
            ->getContainer()
            ->get('App\Service\SecureSessionService')
        ;
        $signRequests = array();
        $signRequest = new SignRequest();
        $signRequest->setAppId('https://172.16.238.10');
        $signRequest->setChallenge('lXaq82clJBmXNnNWL1W6GA');
        $signRequest->setKeyHandle(base64_decode('v8IplXz0zSQUXVYjvSWNcP/70AamVDoaROr1UcREnWaARrRABftdhhaKTFsYTgOj5CH6BUYxztAN9qrU3WcBZg=='));
        $signRequests[1] = $signRequest;
        $requestId = $session->store(serialize($signRequests));
        $postUpLoginButton = $this
            ->getClient()
            ->getCrawler()
            ->selectButton('u2f_login[submit]')
        ;
        $form = $postUpLoginButton->form(array(
            'u2f_login[u2fAuthenticationRequestId]' => $requestId,
            'u2f_login[u2fTokenResponse]' => '{"keyHandle":"v8IplXz0zSQUXVYjvSWNcP_70AamVDoaROr1UcREnWaARrRABftdhhaKTFsYTgOj5CH6BUYxztAN9qrU3WcBZg","clientData":"eyJ0eXAiOiJuYXZpZ2F0b3IuaWQuZ2V0QXNzZXJ0aW9uIiwiY2hhbGxlbmdlIjoibFhhcTgyY2xKQm1YTm5OV0wxVzZHQSIsIm9yaWdpbiI6Imh0dHBzOi8vMTcyLjE2LjIzOC4xMCIsImNpZF9wdWJrZXkiOiJ1bnVzZWQifQ","signatureData":"AQAAAIkwRgIhAN1YRiOqMs1fOCOm7MuOxfYJ6qN7A8PdXrhEzejtw3gNAiEAgi0JJmODYRTN8qflhBNsAjuDkJz06hTUZi2LNbaU4gk"}',
        ));

        $validateLogin = $this
            ->getClient()
            ->submit($form)
        ;
        $this->getClient()->followRedirect();
    }

    public function logOut()
    {
        $logout = $this
            ->getClient()
            ->request('GET', '/authenticated/log-out');
        $button = $logout->selectButton('user_confirmation[submit]');
        $form = $button->form();
        $this->getClient()->submit($form);
    }

    public function resetU2fCounter()
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $ubs = $this->getContainer()->get('App\Service\U2FTokenBuilderService');
        $repo = $doctrine->getRepository(U2FToken::class);
        $oldU2fToken = $repo->find(2);
        $ub = $ubs->createBuilder($oldU2fToken);
        $newU2fToken = $ub->setCounter(0);
        $om = $doctrine->getManager();
        $om->remove($oldU2fToken);
        $om->flush();
        $om->persist($newU2fToken);
        $om->flush();
    }
}