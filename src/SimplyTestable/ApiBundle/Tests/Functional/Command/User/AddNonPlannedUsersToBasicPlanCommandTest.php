<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\User;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\ConsoleCommandTestCase;

class AddNonPlannedUsersToBasicPlanCommandTest extends ConsoleCommandTestCase
{
    /**
     * @var UserFactory
     */
    private $userFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->userFactory = new UserFactory($this->container);
    }

    /**
     * @return string
     */
    protected function getCommandName()
    {
        return 'simplytestable:user:add-non-planned-users-to-basic-plan';
    }

    /**
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand[]
     */
    protected function getAdditionalCommands()
    {
        return array(
            new \SimplyTestable\ApiBundle\Command\User\AddNonPlannedUsersToBasicPlanCommand()
        );
    }

    public function testAssignInMaintenanceReadOnlyModeReturnsStatusCode1()
    {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
        $this->assertReturnCode(1);
        $this->executeCommand('simplytestable:maintenance:disable-read-only');
    }

    public function testPublicUserIsNotAssignedBasicPlan()
    {
        $this->assertReturnCode(0);

        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($this->getUserService()->getPublicUser());
        $this->assertEquals('public', $userAccountPlan->getPlan()->getName());
    }

    public function testAdminUserIsNotAssignedBasicPlan()
    {
        $this->assertReturnCode(0);
        $this->assertNull($this->getUserAccountPlanService()->getForUser($this->getUserService()->getAdminUser()));
    }

    public function testRegularUsersWithoutPlansAreAssignedTheBasicPlanWhenNoUsersHavePlans()
    {
        $userEmailAddresses = array(
            'user1@example.com',
            'user2@example.com',
            'user3@example.com'
        );

        $users = array();

        foreach ($userEmailAddresses as $userEmailAddress) {
            $user = $this->userFactory->create($userEmailAddress);

            $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);
            $this->getManager()->remove($userAccountPlan);
            $this->getManager()->flush();

            $users[] = $user;
        }

        foreach ($users as $user) {
            $this->assertNull($this->getUserAccountPlanService()->getForUser($user));
        }

        $this->assertReturnCode(0);

        foreach ($users as $user) {
            $this->assertEquals('basic', $this->getUserAccountPlanService()->getForUser($user)->getPlan()->getName());
        }
    }

    public function testRegularUsersWithoutPlansAreAssignedTheBasicPlanWhenSomeUsersHavePlans()
    {
        $user1 = $this->userFactory->create('user1@example.com');

        $fooPlan = $this->createAccountPlan('test-foo-plan');

        $this->getUserAccountPlanService()->subscribe($user1, $fooPlan);

        $userEmailAddresses = array(
            'user2@example.com',
            'user3@example.com'
        );

        $users = array();

        foreach ($userEmailAddresses as $userEmailAddress) {
            $user = $this->userFactory->create($userEmailAddress);

            $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);
            $this->getManager()->remove($userAccountPlan);
            $this->getManager()->flush();

            $users[] = $user;
        }

        foreach ($users as $user) {
            $this->assertNull($this->getUserAccountPlanService()->getForUser($user));
        }

        $this->assertReturnCode(0);

        foreach ($users as $user) {
            $this->assertEquals('basic', $this->getUserAccountPlanService()->getForUser($user)->getPlan()->getName());
        }

        $this->assertEquals(
            'test-foo-plan',
            $this->getUserAccountPlanService()->getForUser($user1)->getPlan()->getName()
        );
    }
}
