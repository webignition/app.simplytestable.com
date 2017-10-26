<?php

namespace SimplyTestable\ApiBundle\Tests\Functional;

use Mockery\MockInterface;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint as AccountPlanConstraint;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\TimePeriod;
use SimplyTestable\ApiBundle\Services\AccountPlanService;
use SimplyTestable\ApiBundle\Services\CrawlJobContainerService;
use SimplyTestable\ApiBundle\Services\JobPreparationService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\StripeEventService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Services\Team\InviteService;
use SimplyTestable\ApiBundle\Services\Team\MemberService as TeamMemberService;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use SimplyTestable\ApiBundle\Services\TestHttpClientService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use SimplyTestable\ApiBundle\Services\UserEmailChangeRequestService;
use SimplyTestable\ApiBundle\Services\WebSiteService;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

abstract class BaseSimplyTestableTestCase extends BaseTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->clearRedis();
    }

    /**
     * @return StateService
     */
    protected function getStateService()
    {
        return $this->container->get('simplytestable.services.stateservice');
    }

    /**
     * @return UserEmailChangeRequestService
     */
    protected function getUserEmailChangeRequestService()
    {
        return $this->container->get('simplytestable.services.useremailchangerequestservice');
    }

    /**
     * @return TestHttpClientService
     */
    protected function getHttpClientService()
    {
        return $this->container->get('simplytestable.services.httpclientservice');
    }

    /**
     * @return TestStripeService
     */
    protected function getStripeService()
    {
        return $this->container->get('simplytestable.services.stripeservice');
    }

    /**
     * @return StripeEventService
     */
    protected function getStripeEventService()
    {
        return $this->container->get('simplytestable.services.stripeeventservice');
    }

    /**
     * @param string $baseUrl
     * @param int $count
     *
     * @return string[]
     */
    protected function createUrlResultSet($baseUrl, $count, $offset = 0)
    {
        $urlResultSet = array();

        for ($index = $offset; $index < $count + $offset; $index++) {
            $urlResultSet[] = $baseUrl . $index . '/';
        }

        return $urlResultSet;
    }

    /**
     * @param array $fixtures
     */
    protected function queueHttpFixtures($fixtures)
    {
        foreach ($fixtures as $fixture) {
            $this->getHttpClientService()->queueFixture($fixture);
        }
    }

    /**
     * @return TeamMemberService
     */
    protected function getTeamMemberService()
    {
        return $this->container->get('simplytestable.services.teammemberservice');
    }

    /**
     * @return TeamService
     */
    protected function getTeamService()
    {
        return $this->container->get('simplytestable.services.teamservice');
    }

    /**
     * @return InviteService
     */
    protected function getTeamInviteService()
    {
        return $this->container->get('simplytestable.services.teaminviteservice');
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
