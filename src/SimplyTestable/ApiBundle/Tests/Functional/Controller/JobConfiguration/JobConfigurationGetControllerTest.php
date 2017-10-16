<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration;

use SimplyTestable\ApiBundle\Controller\JobConfiguration\GetController;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\JobConfigurationFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

class JobConfigurationGetControllerTest extends BaseSimplyTestableTestCase
{
    /**
     * @var GetController
     */
    private $jobConfigurationGetController;

    /**
     * @var JobConfiguration
     */
    private $jobConfiguration;

    /**
     * @var User
     */
    private $user;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobConfigurationGetController = new GetController();
        $this->jobConfigurationGetController->setContainer($this->container);

        $userFactory = new UserFactory($this->container);
        $this->user = $userFactory->create();
        $this->setUser($this->user);

        $jobConfigurationFactory = new JobConfigurationFactory($this->container);
        $this->jobConfiguration = $jobConfigurationFactory->create([
            JobConfigurationFactory::KEY_USER => $this->user,
        ]);
    }

    public function testRequest()
    {
        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create();

        $router = $this->container->get('router');
        $requestUrl = $router->generate('jobconfiguration_get_get', [
            'label' => $this->jobConfiguration->getLabel(),
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'user' => $user,
        ]);

        /* @var RedirectResponse $response */
        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testGetActionJobConfigurationNotFound()
    {
        $response = $this->jobConfigurationGetController->getAction('foo');

        $this->assertTrue($response->isNotFound());
    }

    public function testGetActionSuccess()
    {
        $response = $this->jobConfigurationGetController->getAction($this->jobConfiguration->getLabel());

        $this->assertTrue($response->isSuccessful());

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(
            [
                'label' => $this->jobConfiguration->getLabel(),
                'user' => $this->jobConfiguration->getUser()->getEmailCanonical(),
                'website' => $this->jobConfiguration->getWebsite()->getCanonicalUrl(),
                'type' => $this->jobConfiguration->getType()->getName(),
                'task_configurations' => [],
            ],
            $responseData
        );
    }
}
