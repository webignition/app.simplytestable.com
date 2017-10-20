<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob;

use SimplyTestable\ApiBundle\Controller\ScheduledJob\GetController;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\ScheduledJob\Service as ScheduledJobService;
use SimplyTestable\ApiBundle\Tests\Factory\JobConfigurationFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ScheduledJobGetControllerTest extends BaseSimplyTestableTestCase
{
    /**
     * @var GetController
     */
    private $scheduledJobGetController;

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

        $this->scheduledJobGetController = new GetController();
        $this->scheduledJobGetController->setContainer($this->container);

        $userFactory = new UserFactory($this->container);
        $this->user = $userFactory->create();

        $this->setUser($this->user);

        $jobConfigurationFactory = new JobConfigurationFactory($this->container);
        $this->jobConfiguration = $jobConfigurationFactory->create([
            JobConfigurationFactory::KEY_USER => $this->user,
        ]);

        $this->scheduledJobService = $this->container->get('simplytestable.services.scheduledjob.service');
    }

    public function testGetRequest()
    {
        $scheduledJob = $this->scheduledJobService->create($this->jobConfiguration);

        $router = $this->container->get('router');
        $requestUrl = $router->generate('scheduledjob_get_get', [
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

    public function testGetActionScheduledJobNotFound()
    {
        $this->setExpectedException(NotFoundHttpException::class);

        $this->scheduledJobGetController->getAction(0);
    }

    /**
     * @dataProvider getSuccessDataProvider
     *
     * @param string $schedule
     * @param string $cronModifier
     * @param bool $isRecurring
     * @param array $expectedResponseData
     */
    public function testGetSuccess($schedule, $cronModifier, $isRecurring, $expectedResponseData)
    {
        $scheduledJob = $this->scheduledJobService->create(
            $this->jobConfiguration,
            $schedule,
            $cronModifier,
            $isRecurring
        );

        $response = $this->scheduledJobGetController->getAction($scheduledJob->getId());

        $this->assertTrue($response->isSuccessful());

        $responseData = json_decode($response->getContent(), true);

        $expectedResponseData = array_merge(['id' => $scheduledJob->getId()], $expectedResponseData);

        $this->assertEquals($expectedResponseData, $responseData);
    }

    /**
     * @return array
     */
    public function getSuccessDataProvider()
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
