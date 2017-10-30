<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\ScheduledJob\ExecuteCommand;

use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\JobUserAccountPlanEnforcementService;
use SimplyTestable\ApiBundle\Tests\Factory\JobConfigurationFactory;
use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Command\ScheduledJob\ExecuteCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ExecuteCommandTest extends AbstractBaseTestCase
{
    /**
     * @var ExecuteCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = $this->container->get('simplytestable.command.scheduledjob.execute');
    }

    public function testRunInMaintenanceReadOnlyMode()
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');

        $returnCode = $this->command->run(new ArrayInput([
            'id' => 1,
        ]), new BufferedOutput());

        $this->assertEquals(
            ExecuteCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );

        $this->assertTrue($resqueQueueService->contains(
            'scheduledjob-execute',
            ['id' => 1]
        ));

        $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
    }

    public function testRunWithInvalidScheduledJob()
    {
        $returnCode = $this->command->run(new ArrayInput([
            'id' => 1,
        ]), new BufferedOutput());

        $this->assertEquals(
            ExecuteCommand::RETURN_CODE_INVALID_SCHEDULED_JOB,
            $returnCode
        );
    }

    public function testRunForUnroutableWebsite()
    {
        $scheduledJobService = $this->container->get('simplytestable.services.scheduledjob.service');

        $jobConfigurationFactory = new JobConfigurationFactory($this->container);

        $jobConfiguration = $jobConfigurationFactory->create([
            JobConfigurationFactory::KEY_WEBSITE_URL => 'http://foo',
        ]);

        $scheduledJob = $scheduledJobService->create($jobConfiguration);

        $returnCode = $this->command->run(new ArrayInput([
            'id' => $scheduledJob->getId(),
        ]), new BufferedOutput());

        $this->assertEquals(
            ExecuteCommand::RETURN_CODE_UNROUTABLE,
            $returnCode
        );
    }

    public function testRunWithPlanConstraintLimitReached()
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $scheduledJobService = $this->container->get('simplytestable.services.scheduledjob.service');
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $user = $userService->getPublicUser();
        $userAccountPlan = $userAccountPlanService->getForUser($user);
        $plan = $userAccountPlan->getPlan();

        $constraint = $plan->getConstraintNamed(
            JobUserAccountPlanEnforcementService::FULL_SITE_JOBS_PER_SITE_CONSTRAINT_NAME
        );

        $constraint->setLimit(0);

        $entityManager->persist($constraint);
        $entityManager->flush();

        $jobConfigurationFactory = new JobConfigurationFactory($this->container);

        $jobConfiguration = $jobConfigurationFactory->create([
            JobConfigurationFactory::KEY_USER => $user,
        ]);

        $scheduledJob = $scheduledJobService->create($jobConfiguration);

        $returnCode = $this->command->run(new ArrayInput([
            'id' => $scheduledJob->getId(),
        ]), new BufferedOutput());

        $this->assertEquals(
            ExecuteCommand::RETURN_CODE_PLAN_LIMIT_REACHED,
            $returnCode
        );
    }

    public function testRunSuccess()
    {
        $scheduledJobService = $this->container->get('simplytestable.services.scheduledjob.service');

        $jobConfigurationFactory = new JobConfigurationFactory($this->container);

        $jobConfiguration = $jobConfigurationFactory->create();

        $scheduledJob = $scheduledJobService->create($jobConfiguration);

        $returnCode = $this->command->run(new ArrayInput([
            'id' => $scheduledJob->getId(),
        ]), new BufferedOutput());

        $this->assertEquals(
            ExecuteCommand::RETURN_CODE_OK,
            $returnCode
        );
    }
}
