<?php

namespace App\Tests\Functional\Controller\ScheduledJob;

use App\Entity\ScheduledJob;
use App\Entity\User;
use App\Services\ScheduledJob\Service as ScheduledJobService;
use App\Tests\Factory\JobConfigurationFactory;
use App\Tests\Factory\UserFactory;

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

        $userFactory = new UserFactory(self::$container);
        $this->user = $userFactory->createAndActivateUser();

        $this->setUser($this->user);

        $jobConfigurationFactory = new JobConfigurationFactory(self::$container);
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
