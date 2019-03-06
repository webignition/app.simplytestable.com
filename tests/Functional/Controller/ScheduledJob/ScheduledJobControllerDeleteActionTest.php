<?php

namespace App\Tests\Functional\Controller\ScheduledJob;

use App\Entity\ScheduledJob;
use App\Entity\User;
use App\Services\ScheduledJob\Service as ScheduledJobService;
use App\Tests\Services\UserFactory;
use App\Tests\Services\JobConfigurationFactory;

/**
 * @group Controller/ScheduledJob
 */
class ScheduledJobControllerDeleteActionTest extends AbstractScheduledJobControllerTest
{
    /**
     * @var ScheduledJob
     */
    private $scheduledJob;

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

        $jobConfiguration = $jobConfigurationFactory->create([
            JobConfigurationFactory::KEY_USER => $this->user,
        ]);

        $scheduledJobService = self::$container->get(ScheduledJobService::class);
        $this->scheduledJob = $scheduledJobService->create($jobConfiguration);
    }

    public function testDeleteActionGetRequest()
    {
        $router = self::$container->get('router');
        $requestUrl = $router->generate('scheduledjob_delete', [
            'id' => 0,
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'GET',
            'user' => $this->user,
        ]);

        $response = $this->getClientResponse();

        $this->assertEquals(405, $response->getStatusCode());
    }

    public function testDeleteActionPostRequest()
    {
        $router = self::$container->get('router');
        $requestUrl = $router->generate('scheduledjob_delete', [
            'id' => $this->scheduledJob->getId(),
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'POST',
            'user' => $this->user,
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }
}
