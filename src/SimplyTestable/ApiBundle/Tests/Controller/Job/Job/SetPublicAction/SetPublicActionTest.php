<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\SetPublicAction;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class SetPublicActionTest extends BaseControllerJsonTestCase {

    const CANONICAL_URL = 'http://example.com/';
   
    public function testSetPublicByPublicUserForJobOwnedByPublicUser() {
        $jobId = $this->createJobAndGetId(self::CANONICAL_URL);
        
        $response = $this->getJobController('setPublicAction')->setPublicAction(self::CANONICAL_URL, $jobId);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($this->getJobService()->getById($jobId)->getIsPublic());
    }

    public function testSetPublicByNonPublicUserForJobOwnedBySameNonPublicUser() {
        $user = $this->createAndActivateUser('user@example.com', 'password1');
        
        $jobId = $this->createJobAndGetId(self::CANONICAL_URL, $user->getEmail());

        $response = $this->getJobController('setPublicAction', array(
            'user' => $user->getEmail()
        ))->setPublicAction(self::CANONICAL_URL, $jobId);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($this->getJobService()->getById($jobId)->getIsPublic());
    }    

    public function testSetPublicByNonPublicUserForJobOwnedByPublicUser() {
        $user = $this->createAndActivateUser('user@example.com', 'password1');
        
        $jobId = $this->createJobAndGetId(self::CANONICAL_URL);

        $response = $this->getJobController('setPublicAction', array(
            'user' => $user->getEmail()
        ))->setPublicAction(self::CANONICAL_URL, $jobId);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($this->getJobService()->getById($jobId)->getIsPublic());
    }   

    public function testSetPublicByNonPublicUserForJobOwnedByDifferentNonPublicUser() {
        $user1 = $this->createAndActivateUser('user1@example.com', 'password1');
        $user2 = $this->createAndActivateUser('user2@example.com', 'password1');
        
        $jobId = $this->createJobAndGetId(self::CANONICAL_URL, $user1->getEmail());

        $response = $this->getJobController('setPublicAction', array(
            'user' => $user2->getEmail()
        ))->setPublicAction(self::CANONICAL_URL, $jobId);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($this->getJobService()->getById($jobId)->getIsPublic());
    }  
    
    public function testSetPublicByTeamLeaderForJobOwnedByTeamMember() {
        $leader = $this->createAndActivateUser('leader@example.com');
        $member = $this->createAndActivateUser('member@example.com');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $this->getTeamMemberService()->add($team, $member);

        $jobId = $this->createJobAndGetId(self::CANONICAL_URL, $member->getEmail());

        $response = $this->getJobController('setPublicAction', array(
            'user' => $leader->getEmail()
        ))->setPublicAction(self::CANONICAL_URL, $jobId);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($this->getJobService()->getById($jobId)->getIsPublic());
    }


    public function testSetPublicByTeamMemberForJobOwnedByTeamLeader() {
        $leader = $this->createAndActivateUser('leader@example.com');
        $member = $this->createAndActivateUser('member@example.com');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $this->getTeamMemberService()->add($team, $member);

        $jobId = $this->createJobAndGetId(self::CANONICAL_URL, $leader->getEmail());

        $response = $this->getJobController('setPublicAction', array(
            'user' => $member->getEmail()
        ))->setPublicAction(self::CANONICAL_URL, $jobId);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($this->getJobService()->getById($jobId)->getIsPublic());
    }


    public function testSetPublicByTeamMemberForJobOwnedByDifferentTeamMember() {
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

        $response = $this->getJobController('setPublicAction', array(
            'user' => $member2->getEmail()
        ))->setPublicAction(self::CANONICAL_URL, $jobId);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($this->getJobService()->getById($jobId)->getIsPublic());
    }

    
}


