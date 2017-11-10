<?php

namespace Tests\ApiBundle\Functional\Command\Maintenance;

use SimplyTestable\ApiBundle\Command\Maintenance\ModifyInitialStartTrialPeriodCommand;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ModifyInitialStartTrialPeriodCommandTest extends AbstractBaseTestCase
{
    /**
     * @var ModifyInitialStartTrialPeriodCommand
     */
    private $command;

    protected function setUp()
    {
        parent::setUp();

        $this->command = $this->container->get('simplytestable.command.maintenance.modifyinitialstarttrialperiod');
    }

    /**
     * @dataProvider runInvalidRequiredOptionDataProvider
     *
     * @param array $args
     */
    public function testRunInvalidRequiredOption($args)
    {
        $returnCode = $this->command->run(new ArrayInput($args), new BufferedOutput());

        $this->assertEquals(
            ModifyInitialStartTrialPeriodCommand::RETURN_CODE_MISSING_REQUIRED_OPTION,
            $returnCode
        );
    }

    /**
     * @return array
     */
    public function runInvalidRequiredOptionDataProvider()
    {
        return [
            'no args' => [
                'args' => [],
            ],
            'missing new' => [
                'args' => [
                    '--current' => 1,
                ],
            ],
            'invalid new' => [
                'args' => [
                    '--current' => 1,
                    '--new' => -1,
                ],
            ],
            'missing current' => [
                'args' => [
                    '--new' => 1,
                ],
            ],
            'invalid current' => [
                'args' => [
                    '--current' => -1,
                    '--new' => 1,
                ],
            ],
        ];
    }

    /**
     * @dataProvider runDataProvider
     *
     * @param array $userAccountPlanStartTrialPeriods
     * @param array $userAccountPlanIsActiveStatuses
     * @param array $args
     * @param array $expectedStartTrialPeriods
     */
    public function testRun(
        $userAccountPlanStartTrialPeriods,
        $userAccountPlanIsActiveStatuses,
        $args,
        $expectedStartTrialPeriods
    ) {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $userAccountPlanRepository = $entityManager->getRepository(UserAccountPlan::class);

        $userFactory = new UserFactory($this->container);
        $users = $userFactory->createPublicPrivateAndTeamUserSet();

        foreach ($users as $userIndex => $user) {
            /* @var UserAccountPlan $userAccountPlan */
            $userAccountPlan = $userAccountPlanRepository->findOneBy([
                'user' => $user,
            ]);

            $userAccountPlan->setStartTrialPeriod($userAccountPlanStartTrialPeriods[$userIndex]);
            $userAccountPlan->setIsActive($userAccountPlanIsActiveStatuses[$userIndex]);
            $entityManager->persist($userAccountPlan);
            $entityManager->flush($userAccountPlan);
        }

        $returnCode = $this->command->run(new ArrayInput($args), new BufferedOutput());

        $this->assertEquals(ModifyInitialStartTrialPeriodCommand::RETURN_CODE_OK, $returnCode);

        /* @var UserAccountPlan[] $userAccountPlans */
        $userAccountPlans = $userAccountPlanRepository->findAll();

        foreach ($userAccountPlans as $userAccountPlanIndex => $userAccountPlan) {
            $this->assertEquals(
                $expectedStartTrialPeriods[$userAccountPlanIndex],
                $userAccountPlan->getStartTrialPeriod()
            );
        }
    }

    /**
     * @return array
     */
    public function runDataProvider()
    {
        return [
            'no matching users' => [
                'userAccountPlanStartTrialPeriods' => [
                    'public' => 2,
                    'private' => 3,
                    'leader' => 4,
                    'member1' => 5,
                    'member2' => 6,
                ],
                'userAccountPlanIsActiveStatuses' => [
                    'public' => true,
                    'private' => true,
                    'leader' => true,
                    'member1' => true,
                    'member2' => true,
                ],
                'args' => [
                    '--current' => 1,
                    '--new' => 1,
                ],
                'expectedStartTrialPeriods' => [
                    2, 3, 4, 5, 6,
                ],
            ],
            'matching users' => [
                'userAccountPlanStartTrialPeriods' => [
                    'public' => 2,
                    'private' => 2,
                    'leader' => 2,
                    'member1' => 5,
                    'member2' => 6,
                ],
                'userAccountPlanIsActiveStatuses' => [
                    'public' => true,
                    'private' => null,
                    'leader' => true,
                    'member1' => true,
                    'member2' => true,
                ],
                'args' => [
                    '--current' => 2,
                    '--new' => 8,
                ],
                'expectedStartTrialPeriods' => [
                    2, 8, 8, 5, 6,
                ],
            ],
            'matching users, dry run' => [
                'userAccountPlanStartTrialPeriods' => [
                    'public' => 2,
                    'private' => 2,
                    'leader' => 2,
                    'member1' => 5,
                    'member2' => 6,
                ],
                'userAccountPlanIsActiveStatuses' => [
                    'public' => true,
                    'private' => null,
                    'leader' => true,
                    'member1' => true,
                    'member2' => true,
                ],
                'args' => [
                    '--dry-run' => true,
                    '--current' => 2,
                    '--new' => 8,
                ],
                'expectedStartTrialPeriods' => [
                    2, 2, 2, 5, 6,
                ],
            ],
        ];
    }
}
