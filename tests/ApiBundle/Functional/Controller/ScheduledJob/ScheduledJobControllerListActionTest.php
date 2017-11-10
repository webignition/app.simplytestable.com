<?php

namespace Tests\ApiBundle\Functional\Controller\ScheduledJob;

use SimplyTestable\ApiBundle\Controller\ScheduledJobController;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\ScheduledJob\Service as ScheduledJobService;
use Tests\ApiBundle\Factory\JobConfigurationFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

class ScheduledJobControllerListActionTest extends AbstractBaseTestCase
{
    /**
     * @var ScheduledJobController
     */
    private $scheduledJobController;

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

        $this->scheduledJobController = new ScheduledJobController();
        $this->scheduledJobController->setContainer($this->container);

        $userFactory = new UserFactory($this->container);
        $this->user = $userFactory->createAndActivateUser();

        $this->setUser($this->user);

        $jobConfigurationFactory = new JobConfigurationFactory($this->container);
        $this->jobConfiguration = $jobConfigurationFactory->create([
            JobConfigurationFactory::KEY_USER => $this->user,
        ]);

        $this->scheduledJobService = $this->container->get(ScheduledJobService::class);
    }

    public function testListActionGetRequest()
    {
        $this->scheduledJobService->create($this->jobConfiguration);

        $router = $this->container->get('router');
        $requestUrl = $router->generate('scheduledjob_list');

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'GET',
            'user' => $this->user,
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testListActionEmptyList()
    {
        $response = $this->scheduledJobController->listAction();

        $this->assertTrue($response->isSuccessful());

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals([], $responseData);
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
