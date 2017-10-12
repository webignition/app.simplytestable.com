<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration;

use SimplyTestable\ApiBundle\Controller\JobConfiguration\GetListController;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\JobConfigurationFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

class JobConfigurationGetListControllerTest extends BaseSimplyTestableTestCase
{
    /**
     * @var GetListController
     */
    private $jobConfigurationGetListController;

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

        $this->jobConfigurationGetListController = new GetListController();
        $this->jobConfigurationGetListController->setContainer($this->container);

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
        $router = $this->container->get('router');
        $requestUrl = $router->generate('jobconfiguration_getlist_list');

        $this->getCrawler([
            'url' => $requestUrl,
            'user' => $this->user,
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testListAction()
    {
        $response = $this->jobConfigurationGetListController->listAction();

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
