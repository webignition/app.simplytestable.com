<?php

namespace App\Tests\Functional\Command\ScheduledJob\ExecuteCommand;

use App\Services\JobUserAccountPlanEnforcementService;
use App\Services\ScheduledJob\Service as ScheduledJobService;
use App\Services\UserAccountPlanService;
use App\Services\UserService;
use App\Tests\Factory\JobConfigurationFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Command\ScheduledJob\ExecuteCommand;
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

        $this->command = self::$container->get(ExecuteCommand::class);
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
        $scheduledJobService = self::$container->get(ScheduledJobService::class);

        $jobConfigurationFactory = new JobConfigurationFactory(self::$container);

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
        $userService = self::$container->get(UserService::class);
        $scheduledJobService = self::$container->get(ScheduledJobService::class);
        $userAccountPlanService = self::$container->get(UserAccountPlanService::class);
        $entityManager = self::$container->get('doctrine.orm.entity_manager');

        $user = $userService->getPublicUser();
        $userAccountPlan = $userAccountPlanService->getForUser($user);
        $plan = $userAccountPlan->getPlan();

        $constraint = $plan->getConstraintNamed(
            JobUserAccountPlanEnforcementService::FULL_SITE_JOBS_PER_SITE_CONSTRAINT_NAME
        );

        $constraint->setLimit(0);

        $entityManager->persist($constraint);
        $entityManager->flush();

        $jobConfigurationFactory = new JobConfigurationFactory(self::$container);

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
        $scheduledJobService = self::$container->get(ScheduledJobService::class);

        $jobConfigurationFactory = new JobConfigurationFactory(self::$container);

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
