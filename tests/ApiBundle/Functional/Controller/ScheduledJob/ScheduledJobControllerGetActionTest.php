<?php

namespace Tests\ApiBundle\Functional\Controller\ScheduledJob;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\ScheduledJob\Service as ScheduledJobService;
use Tests\ApiBundle\Factory\JobConfigurationFactory;
use Tests\ApiBundle\Factory\UserFactory;

/**
 * @group Controller/ScheduledJob
 */
class ScheduledJobControllerGetActionTest extends AbstractScheduledJobControllerTest
{
    /**
     * @var ScheduledJobService
     */
    private $scheduledJobService;

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

        $userFactory = new UserFactory($this->container);
        $this->user = $userFactory->createAndActivateUser();

        $this->setUser($this->user);

        $jobConfigurationFactory = new JobConfigurationFactory($this->container);
        $this->jobConfiguration = $jobConfigurationFactory->create([
            JobConfigurationFactory::KEY_USER => $this->user,
        ]);

        $this->scheduledJobService = $this->container->get(ScheduledJobService::class);
    }

    public function testGetActionGetRequest()
    {
        $scheduledJob = $this->scheduledJobService->create($this->jobConfiguration);

        $router = $this->container->get('router');
        $requestUrl = $router->generate('scheduledjob_get', [
            'id' => $scheduledJob->getId(),
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'GET',
            'user' => $this->user,
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }
}
