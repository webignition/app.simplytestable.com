<?php

namespace App\Tests\Functional\Controller\ScheduledJob;

use App\Entity\Job\Configuration as JobConfiguration;
use App\Entity\User;
use App\Services\ScheduledJob\Service as ScheduledJobService;
use App\Tests\Factory\JobConfigurationFactory;
use App\Tests\Factory\UserFactory;

/**
 * @group Controller/ScheduledJob
 */
class ScheduledJobControllerListActionTest extends AbstractScheduledJobControllerTest
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

        $userFactory = new UserFactory(self::$container);
        $this->user = $userFactory->createAndActivateUser();

        $this->setUser($this->user);

        $jobConfigurationFactory = new JobConfigurationFactory(self::$container);
        $this->jobConfiguration = $jobConfigurationFactory->create([
            JobConfigurationFactory::KEY_USER => $this->user,
        ]);

        $this->scheduledJobService = self::$container->get(ScheduledJobService::class);
    }

    public function testListActionGetRequest()
    {
        $this->scheduledJobService->create($this->jobConfiguration);

        $router = self::$container->get('router');
        $requestUrl = $router->generate('scheduledjob_list');

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'GET',
            'user' => $this->user,
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    /**
     * @dataProvider listActionSuccessDataProvider
     *
     * @param string $schedule
     * @param string $cronModifier
     * @param bool $isRecurring
     * @param array $expectedResponseData
     */
    public function testListActionSuccess($schedule, $cronModifier, $isRecurring, $expectedResponseData)
    {
        $scheduledJob = $this->scheduledJobService->create(
            $this->jobConfiguration,
            $schedule,
            $cronModifier,
            $isRecurring
        );

        $response = $this->scheduledJobController->listAction();

        $this->assertTrue($response->isSuccessful());

        $responseData = json_decode($response->getContent(), true);

        $expectedResponseData = [
            array_merge(['id' => $scheduledJob->getId()], $expectedResponseData),
        ];

        $this->assertEquals($expectedResponseData, $responseData);
    }

    /**
     * @return array
     */
    public function listActionSuccessDataProvider()
    {
        return [
            'without cron modififer' => [
                'schedule' => '* * * * *',
                'cronModifier' => null,
                'isRecurring' => false,
                'expectedResponseData' => [
                    'jobconfiguration' => 'label',
                    'schedule' => '* * * * *',
                    'isrecurring' => 0,
                ],
            ],
            'with cron modififer' => [
                'schedule' => '* * * * *',
                'cronModifier' => 'foo',
                'isRecurring' => false,
                'expectedResponseData' => [
                    'jobconfiguration' => 'label',
                    'schedule' => '* * * * *',
                    'isrecurring' => 0,
                    'schedule-modifier' => 'foo',
                ],
            ],
        ];
    }
}
