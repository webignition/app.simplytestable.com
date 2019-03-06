<?php

namespace App\Tests\Functional\Command\User;

use App\Command\User\AddNonPlannedUsersToBasicPlanCommand;
use App\Entity\User;
use App\Services\UserAccountPlanService;
use App\Tests\Services\UserFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class AddNonPlannedUsersToBasicPlanCommandTest extends AbstractBaseTestCase
{
    /**
     * @var AddNonPlannedUsersToBasicPlanCommand
     */
    private $command;

    protected function setUp()
    {
        parent::setUp();

        $this->command = self::$container->get(AddNonPlannedUsersToBasicPlanCommand::class);
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
        $userFactory = self::$container->get(UserFactory::class);
        $userAccountPlanService = self::$container->get(UserAccountPlanService::class);

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
