<?php

namespace SimplyTestable\ApiBundle\Tests\Functional;

use Mockery\MockInterface;
use SimplyTestable\ApiBundle\Entity\User;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

abstract class BaseSimplyTestableTestCase extends AbstractBaseTestCase
{
    /**
     * @param array $fixtures
     */
    protected function queueHttpFixtures($fixtures)
    {
        $httpClientService = $this->container->get('simplytestable.services.httpclientservice');

        foreach ($fixtures as $fixture) {
            $httpClientService->queueFixture($fixture);
        }
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
            $userService = $this->container->get('simplytestable.services.userservice');

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
     * @return Response
     */
    protected function getClientResponse()
    {
        /* @var Response $response */
        $response = $this->client->getResponse();

        return $response;
    }

    /**
     * @param User $user
     */
    private function setRequestUserInSession(User $user)
    {
        $session = $this->container->get('session');
        $loginManager = $this->container->get('fos_user.security.login_manager');
        $firewallName = $this->container->getParameter('fos_user.firewall_name');

        $loginManager->loginUser($firewallName, $user);

        $this->container->get('session')->set(
            '_security_' . $firewallName,
            serialize($this->container->get('security.token_storage')->getToken())
        );

        $this->container->get('session')->save();
        $this->client->getCookieJar()->set(new Cookie($session->getName(), $session->getId()));
    }

    /**
     * @param User $user
     */
    protected function setUser(User $user)
    {
        $securityTokenStorage = $this->container->get('security.token_storage');

        /* @var MockInterface|TokenInterface */
        $token = \Mockery::mock(TokenInterface::class);
        $token
            ->shouldReceive('getUser')
            ->andReturn($user);

        $securityTokenStorage->setToken($token);
    }
}
