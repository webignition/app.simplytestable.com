<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\User;

use SimplyTestable\ApiBundle\Command\User\AddNonPlannedUsersToBasicPlanCommand;
use SimplyTestable\ApiBundle\Controller\MaintenanceController;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Functional\ConsoleCommandTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class AddNonPlannedUsersToBasicPlanCommandTest extends BaseSimplyTestableTestCase
{
    /**
     * @var AddNonPlannedUsersToBasicPlanCommand
     */
    private $command;

//    /**
//     * @var UserFactory
//     */
//    private $userFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->command = $this->container->get('simplytestable.command.user.addnonplanneduserstobascicplan');

//        $this->userFactory = new UserFactory($this->container);
    }

    public function testRunInMaintenanceReadOnlyModeReturnsStatusCode1()
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        $returnCode = $this->command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(
            AddNonPlannedUsersToBasicPlanCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );

        $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
    }

    /**
     * @dataProvider runDataProvider
     *
     * @param array $userValuesCollection
     * @param array $args
     * @param array $expectedUserPlanNames
     */
    public function testRun($userValuesCollection, $args, $expectedUserPlanNames)
    {
        $userFactory = new UserFactory($this->container);
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');

        /* @var User[] $users */
        $users = [];

        foreach ($userValuesCollection as $userValues) {
            $users[] = $userFactory->create($userValues);
        }

        $returnCode = $this->command->run(new ArrayInput($args), new BufferedOutput());

        $this->assertEquals(
            AddNonPlannedUsersToBasicPlanCommand::RETURN_CODE_OK,
            $returnCode
        );

        foreach ($users as $userIndex => $user) {
            $userAccountPlan = $userAccountPlanService->getForUser($user);

            $expectedUserPlanName = $expectedUserPlanNames[$userIndex];

            if (empty($expectedUserPlanName)) {
                $this->assertNull($userAccountPlan);
            } else {
                $this->assertEquals(
                    $expectedUserPlanName,
                    $userAccountPlan->getPlan()->getName()
                );
            }
        }
    }

    /**
     * @return array
     */
    public function runDataProvider()
    {
        return [
            'no users' => [
                'userValuesCollection' => [],
                'args' => [],
                'expectedUserPlanNames' => [],
            ],
            'selection of users' => [
                'userValuesCollection' => [
                    [
                        UserFactory::KEY_EMAIL => 'public@simplytestable.com',
                    ],
                    [
                        UserFactory::KEY_EMAIL => 'no-plan@simplytestable.com',
                        UserFactory::KEY_PLAN_NAME => null,
                    ],
                    [
                        UserFactory::KEY_EMAIL => 'agency@simplytestable.com',
                        UserFactory::KEY_PLAN_NAME => 'agency',
                    ],
                ],
                'args' => [],
                'expectedUserPlanNames' => [
                    'public',
                    'basic',
                    'agency',
                ],
            ],
            'selection of users; dry-run' => [
                'userValuesCollection' => [
                    [
                        UserFactory::KEY_EMAIL => 'public@simplytestable.com',
                    ],
                    [
                        UserFactory::KEY_EMAIL => 'no-plan@simplytestable.com',
                        UserFactory::KEY_PLAN_NAME => null,
                    ],
                    [
                        UserFactory::KEY_EMAIL => 'agency@simplytestable.com',
                        UserFactory::KEY_PLAN_NAME => 'agency',
                    ],
                ],
                'args' => [
                    '--dry-run' => true,
                ],
                'expectedUserPlanNames' => [
                    'public',
                    null,
                    'agency',
                ],
            ],
        ];
    }
}
