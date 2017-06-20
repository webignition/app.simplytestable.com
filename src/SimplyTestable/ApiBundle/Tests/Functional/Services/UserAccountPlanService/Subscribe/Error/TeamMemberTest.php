<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\UserAccountPlanService\Subscribe\Error;

use SimplyTestable\ApiBundle\Tests\Functional\Services\UserAccountPlanService\ServiceTest;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Exception\Services\UserAccountPlan\Exception as UserAccountPlanServiceException;

class TeamMemberTest extends ServiceTest {

    /**
     * @var User
     */
    private $user;

    public function setUp() {
        parent::setUp();

        $leader = $this->createAndActivateUser('leader@example.com', 'password');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $this->user = $this->createAndActivateUser('user@example.com', 'password');
        $this->getTeamMemberService()->add($team, $this->user);
    }

    public function testTeamMemberSubscribeToPlanThrowsException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\UserAccountPlan\Exception',
            '',
            UserAccountPlanServiceException::CODE_USER_IS_TEAM_MEMBER
        );

        $accountPlan = $this->getAccountPlanService()->find('basic');
        $this->getUserAccountPlanService()->subscribe($this->user, $accountPlan);
    }

}
