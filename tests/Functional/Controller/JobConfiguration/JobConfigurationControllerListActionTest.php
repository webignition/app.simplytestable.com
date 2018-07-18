<?php

namespace App\Tests\Functional\Controller\JobConfiguration;

use App\Entity\User;
use App\Tests\Factory\JobConfigurationFactory;
use App\Tests\Factory\UserFactory;
use App\Entity\Job\Configuration as JobConfiguration;

/**
 * @group Controller/JobConfiguration
 */
class JobConfigurationControllerListActionTest extends AbstractJobConfigurationControllerTest
{
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

        $userFactory = new UserFactory(self::$container);
        $this->user = $userFactory->createAndActivateUser();
        $this->setUser($this->user);

        $jobConfigurationFactory = new JobConfigurationFactory(self::$container);
        $this->jobConfiguration = $jobConfigurationFactory->create([
            JobConfigurationFactory::KEY_USER => $this->user,
        ]);
    }

    public function testListActionGetRequest()
    {
        $router = self::$container->get('router');
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
