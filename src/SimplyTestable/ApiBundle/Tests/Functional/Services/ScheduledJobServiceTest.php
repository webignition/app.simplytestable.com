<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services;

use Cron\CronBundle\Entity\CronJob;
use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Exception\Services\ScheduledJob\Exception as ScheduledJobServiceException;
use SimplyTestable\ApiBundle\Repository\ScheduledJobRepository;
use SimplyTestable\ApiBundle\Services\Job\ConfigurationService as JobConfigurationService;
use SimplyTestable\ApiBundle\Services\ScheduledJob\Service as ScheduledJobService;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use SimplyTestable\ApiBundle\Tests\Factory\JobConfigurationFactory;
use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;
use Cron\CronBundle\Cron\Manager as CronManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ScheduledJobServiceTest extends AbstractBaseTestCase
{
    /**
     * @var ScheduledJobService
     */
    private $scheduledJobService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->scheduledJobService = $this->container->get('simplytestable.services.scheduledjob.service');
    }

    public function testCreateMatchingScheduledJobExists()
    {
        $scheduledJobRepository = \Mockery::mock(ScheduledJobRepository::class);
        $scheduledJobRepository
            ->shouldReceive('has')
            ->andReturn(true);

        /* @var EntityManager $entityManager */
        $entityManager = \Mockery::mock(EntityManager::class);
        $entityManager
            ->shouldReceive('getRepository')
            ->with(ScheduledJob::class)
            ->andReturn($scheduledJobRepository);

        /* @var JobConfigurationService $jobConfigurationService */
        $jobConfigurationService = \Mockery::mock(JobConfigurationService::class);

        /* @var TeamService $teamService */
        $teamService = \Mockery::mock(TeamService::class);

        /* @var CronManager $cronManager */
        $cronManager = \Mockery::mock(CronManager::class);

        /* @var TokenStorageInterface $tokenStorage */
        $tokenStorage = \Mockery::mock(TokenStorageInterface::class);

        $scheduledJobService = new ScheduledJobService(
            $entityManager,
            $jobConfigurationService,
            $teamService,
            $cronManager,
            $tokenStorage
        );

        /* @var JobConfiguration $jobConfiguration */
        $jobConfiguration = \Mockery::mock(JobConfiguration::class);

        $this->expectException(ScheduledJobServiceException::class);
        $this->expectExceptionMessage('Matching scheduled job exists');
        $this->expectExceptionCode(ScheduledJobServiceException::CODE_MATCHING_SCHEDULED_JOB_EXISTS);

        $scheduledJobService->create($jobConfiguration);
    }

    /**
     * @dataProvider createSuccessDataProvider
     *
     * @param array $jobConfigurationValues
     * @param string $schedule
     * @param string $cronModifier
     * @param bool $isRecurring
     * @param string $expectedCronJobCommand
     */
    public function testCreateSuccess(
        $jobConfigurationValues,
        $schedule,
        $cronModifier,
        $isRecurring,
        $expectedCronJobCommand
    ) {
        $jobConfigurationFactory = new JobConfigurationFactory($this->container);
        $jobConfiguration = $jobConfigurationFactory->create($jobConfigurationValues);

        $scheduledJob = $this->scheduledJobService->create($jobConfiguration, $schedule, $cronModifier, $isRecurring);

        $this->assertInstanceOf(ScheduledJob::class, $scheduledJob);
        $this->assertNotNull($scheduledJob->getId());
        $this->assertEquals($isRecurring, $scheduledJob->getIsRecurring());
        $this->assertEquals($cronModifier, $scheduledJob->getCronModifier());

        $cronJob = $scheduledJob->getCronJob();
        $this->assertInstanceOf(CronJob::class, $cronJob);
        $this->assertNotNull($cronJob->getId());
        $this->assertEquals($schedule, $cronJob->getSchedule());
        $this->assertEquals(
            sprintf($expectedCronJobCommand, $scheduledJob->getId()),
            $cronJob->getCommand()
        );
    }

    /**
     * @return array
     */
    public function createSuccessDataProvider()
    {
        return [
            'without cron modifier' => [
                'jobConfigurationValues' => [
                    JobConfigurationFactory::KEY_LABEL => 'foo',
                ],
                'schedule' => '* * * * * *',
                'cronModifier' => null,
                'isRecurring' => true,
                'expectedCronJobCommand' => 'simplytestable:scheduledjob:enqueue %s'
            ],
            'with cron modifier' => [
                'jobConfigurationValues' => [
                    JobConfigurationFactory::KEY_LABEL => 'foo',
                ],
                'schedule' => '* * * * * *',
                'cronModifier' => 'bar',
                'isRecurring' => true,
                'expectedCronJobCommand' => 'simplytestable:scheduledjob:enqueue %s #bar'
            ],
        ];
    }
}
