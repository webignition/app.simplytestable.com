<?php

namespace Tests\ApiBundle\Functional\Controller\JobConfiguration;

use SimplyTestable\ApiBundle\Controller\JobConfigurationController;
use SimplyTestable\ApiBundle\Entity\User;
use Tests\ApiBundle\Factory\JobConfigurationFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

class JobConfigurationControllerListActionTest extends AbstractBaseTestCase
{
    /**
     * @var JobConfigurationController
     */
    private $jobConfigurationController;

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

        $this->jobConfigurationController = new JobConfigurationController();
        $this->jobConfigurationController->setContainer($this->container);

        $userFactory = new UserFactory($this->container);
        $this->user = $userFactory->createAndActivateUser();
        $this->setUser($this->user);

        $jobConfigurationFactory = new JobConfigurationFactory($this->container);
        $this->jobConfiguration = $jobConfigurationFactory->create([
            JobConfigurationFactory::KEY_USER => $this->user,
        ]);
    }

    public function testListActionGetRequest()
    {
        $router = $this->container->get('router');
        $requestUrl = $router->generate('jobconfiguration_list');

        $this->getCrawler([
            'url' => $requestUrl,
            'user' => $this->user,
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testListAction()
    {
        $response = $this->jobConfigurationController->listAction();

        $this->assertTrue($response->isSuccessful());

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(
            [
                [
                    'label' => $this->jobConfiguration->getLabel(),
                    'user' => $this->jobConfiguration->getUser()->getEmailCanonical(),
                    'website' => $this->jobConfiguration->getWebsite()->getCanonicalUrl(),
                    'type' => $this->jobConfiguration->getType()->getName(),
                    'task_configurations' => [],
                ],
            ],
            $responseData
        );
    }
}
