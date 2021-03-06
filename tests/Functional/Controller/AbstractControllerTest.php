<?php

namespace App\Tests\Functional\Controller;

use FOS\UserBundle\Security\LoginManager;
use App\Entity\User;
use App\Services\UserService;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use App\Tests\Functional\AbstractBaseTestCase;

abstract class AbstractControllerTest extends AbstractBaseTestCase
{
    /**
     * @return Response
     */
    protected function getClientResponse()
    {
        /* @var Response $response */
        $response = $this->client->getResponse();

        return $response;
    }

    /**
     * @param array $options
     *
     * @return Crawler
     */
    protected function getCrawler($options)
    {
        if (!isset($options['url'])) {
            $options['url'] = '';
        }

        if (!isset($options['method'])) {
            $options['method'] = 'GET';
        }

        if (!isset($options['parameters'])) {
            $options['parameters'] = [];
        }

        if (!isset($options['files'])) {
            $options['files'] = [];
        }

        if (!isset($options['server'])) {
            $options['server'] = [];
        }

        if (!array_key_exists('user', $options)) {
            $userService = self::$container->get(UserService::class);

            $options['user']  = $userService->getPublicUser();
        }

        if (!empty($options['user'])) {
            $this->setRequestUserInSession($options['user']);
        }

        $crawler = $this->client->request(
            $options['method'],
            $options['url'],
            $options['parameters'],
            $options['files'],
            $options['server']
        );

        return $crawler;
    }

    /**
     * @param User $user
     */
    private function setRequestUserInSession(User $user)
    {
        $session = self::$container->get('session');
        $loginManager = self::$container->get(LoginManager::class);
        $firewallName = self::$container->getParameter('fos_user.firewall_name');

        $loginManager->loginUser($firewallName, $user);

        self::$container->get('session')->set(
            '_security_' . $firewallName,
            serialize(self::$container->get('security.token_storage')->getToken())
        );

        self::$container->get('session')->save();
        $this->client->getCookieJar()->set(new Cookie($session->getName(), $session->getId()));
    }
}
