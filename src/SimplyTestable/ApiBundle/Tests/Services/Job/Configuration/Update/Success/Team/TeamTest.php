<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Update\Success\Team;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Update\Success\SuccessTest;

abstract class TeamTest extends SuccessTest {

    /**
     * @var User
     */
    protected $leader;


    /**
     * @var User
     */
    protected $member1;

    /**
     * @var User
     */
    protected $member2;


    public function preCreateJobConfigurations() {
        $this->leader = $this->createAndActivateUser('leader@example.com', 'password');
        $this->member1 = $this->createAndActivateUser('user1@example.com');
        $this->member2 = $this->createAndActivateUser('user2@example.com');

        $team = $this->getTeamService()->create(
            'Foo',
            $this->leader
        );

        $this->getTeamMemberService()->add($team, $this->member1);
        $this->getTeamMemberService()->add($team, $this->member2);
    }


    protected function getCurrentUser() {
        return $this->getUserService()->getPublicUser();
    }

    protected function getOriginalWebsite() {
        return $this->getWebSiteService()->fetch('http://original.example.com/');
    }

    protected function getOriginalJobType() {
        return $this->getJobTypeService()->getFullSiteType();
    }

    protected function getOriginalParameters() {
        return 'original-parameters';
    }

    protected function getNewWebsite() {
        return $this->getWebSiteService()->fetch('http://new.example.com/');
    }

    protected function getNewJobType() {
        return $this->getJobTypeService()->getSingleUrlType();
    }

    protected function getNewParameters() {
        return 'new-parameters';
    }

}