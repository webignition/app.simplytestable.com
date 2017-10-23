<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob;

use SimplyTestable\ApiBundle\Controller\ScheduledJob\GetListController;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\ScheduledJob\Service as ScheduledJobService;
use SimplyTestable\ApiBundle\Tests\Factory\JobConfigurationFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class ScheduledJobGetListControllerTest extends BaseSimplyTestableTestCase
{
    /**
     * @var GetListController
     */
    private $scheduledJobGetListController;

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

        $this->scheduledJobGetListController = new GetListController();
        $this->scheduledJobGetListController->setContainer($this->container);

        $userFactory = new UserFactory($this->container);
        $this->user = $userFactory->createAndActivateUser();

        $this->setUser($this->user);

        $jobConfigurationFactory = new JobConfigurationFactory($this->container);
        $this->jobConfiguration = $jobConfigurationFactory->create([
            JobConfigurationFactory::KEY_USER => $this->user,
        ]);

        $this->scheduledJobService = $this->container->get('simplytestable.services.scheduledjob.service');
    }

    public function testGetRequest()
    {
        $this->scheduledJobService->create($this->jobConfiguration);

        $router = $this->container->get('router');
        $requestUrl = $router->generate('scheduledjob_getlist_list');

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'GET',
            'user' => $this->user,
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testGetActionEmptyList()
    {
        $response = $this->scheduledJobGetListController->listAction();

        $this->assertTrue($response->isSuccessful());

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals([], $responseData);
    }

    /**
     * @dataProvider getListSuccessDataProvider
     *
     * @param string $schedule
     * @param string $cronModifier
     * @param bool $isRecurring
     * @param array $expectedResponseData
     */
    public function testGetListSuccess($schedule, $cronModifier, $isRecurring, $expectedResponseData)
    {
        $scheduledJob = $this->scheduledJobService->create(
            $this->jobConfiguration,
            $schedule,
            $cronModifier,
            $isRecurring
        );

        $response = $this->scheduledJobGetListController->listAction();

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
    public function getListSuccessDataProvider()
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
