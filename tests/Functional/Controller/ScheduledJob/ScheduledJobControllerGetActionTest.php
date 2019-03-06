<?php

namespace App\Tests\Functional\Controller\ScheduledJob;

use App\Entity\Job\Configuration as JobConfiguration;
use App\Entity\User;
use App\Services\ScheduledJob\Service as ScheduledJobService;
use App\Tests\Services\UserFactory;
use App\Tests\Services\JobConfigurationFactory;

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

        $jobConfigurationFactory = self::$container->get(JobConfigurationFactory::class);

        $userFactory = self::$container->get(UserFactory::class);
        $this->user = $userFactory->createAndActivateUser();

        $this->setUser($this->user);

        $this->jobConfiguration = $jobConfigurationFactory->create([
            JobConfigurationFactory::KEY_USER => $this->user,
        ]);

        $this->scheduledJobService = self::$container->get(ScheduledJobService::class);
    }

    public function testGetActionGetRequest()
    {
        $scheduledJob = $this->scheduledJobService->create($this->jobConfiguration);

        $router = self::$container->get('router');
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
