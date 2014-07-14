<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\SetPrivateAction;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class SetPrivateActionTest extends BaseControllerJsonTestCase {

    const CANONICAL_URL = 'http://example.com/';

    public function testSetPrivateByPublicUserForJobOwnedByPublicUser() {
        $jobId = $this->createJobAndGetId(self::CANONICAL_URL);

        $response = $this->getJobController('setPrivateAction')->setPrivateAction(self::CANONICAL_URL, $jobId);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($this->getJobService()->getById($jobId)->getIsPublic());
    }


    public function testSetPrivateByNonPublicUserForJobOwnedBySameNonPublicUser() {
        $user = $this->createAndActivateUser('user@example.com', 'password1');

        $jobId = $this->createJobAndGetId(self::CANONICAL_URL, $user->getEmail());

        $response = $this->getJobController('setPrivateAction', array(
            'user' => $user->getEmail()
        ))->setPrivateAction(self::CANONICAL_URL, $jobId);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertFalse($this->getJobService()->getById($jobId)->getIsPublic());
    }


    public function testSetPrivateByNonPublicUserForJobOwnedByPublicUser() {
        $user = $this->createAndActivateUser('user@example.com', 'password1');

        $jobId = $this->createJobAndGetId(self::CANONICAL_URL);

        $response = $this->getJobController('setPublicAction', array(
            'user' => $user->getEmail()
        ))->setPublicAction(self::CANONICAL_URL, $jobId);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($this->getJobService()->getById($jobId)->getIsPublic());
    }

    public function testSetPrivateByNonPublicUserForJobOwnedByDifferentNonPublicUser() {
        $user1 = $this->createAndActivateUser('user1@example.com', 'password1');
        $user2 = $this->createAndActivateUser('user2@example.com', 'password1');

        $jobId = $this->createJobAndGetId(self::CANONICAL_URL, $user1->getEmail());

        $response = $this->getJobController('setPrivateAction', array(
            'user' => $user2->getEmail()
        ))->setPrivateAction(self::CANONICAL_URL, $jobId);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($this->getJobService()->getById($jobId)->getIsPublic());
    }


    public function testSetPrivateByTeamLeaderForJobOwnedByTeamMember() {
        $leader = $this->createAndActivateUser('leader@example.com');
        $member = $this->createAndActivateUser('member@example.com');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $this->getTeamMemberService()->add($team, $member);

        $jobId = $this->createJobAndGetId(self::CANONICAL_URL, $member->getEmail());

        $response = $this->getJobController('setPrivateAction', array(
            'user' => $leader->getEmail()
        ))->setPrivateAction(self::CANONICAL_URL, $jobId);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertFalse($this->getJobService()->getById($jobId)->getIsPublic());
    }


    public function testSetPrivateByTeamMemberForJobOwnedByTeamLeader() {
        $leader = $this->createAndActivateUser('leader@example.com');
        $member = $this->createAndActivateUser('member@example.com');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $this->getTeamMemberService()->add($team, $member);

        $jobId = $this->createJobAndGetId(self::CANONICAL_URL, $leader->getEmail());

        $response = $this->getJobController('setPrivateAction', array(
            'user' => $member->getEmail()
        ))->setPrivateAction(self::CANONICAL_URL, $jobId);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertFalse($this->getJobService()->getById($jobId)->getIsPublic());
    }


    public function testSetPrivateByTeamMemberForJobOwnedByDifferentTeamMember() {
        $leader = $this->createAndActivateUser('leader@example.com');
        $member1 = $this->createAndActivateUser('member1@example.com');
        $member2 = $this->createAndActivateUser('member2@example.com');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $this->getTeamMemberService()->add($team, $member1);
        $this->getTeamMemberService()->add($team, $member2);

        $jobId = $this->createJobAndGetId(self::CANONICAL_URL, $member1->getEmail());

        $response = $this->getJobController('setPrivateAction', array(
            'user' => $member2->getEmail()
        ))->setPrivateAction(self::CANONICAL_URL, $jobId);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertFalse($this->getJobService()->getById($jobId)->getIsPublic());
    }
    
    
}


