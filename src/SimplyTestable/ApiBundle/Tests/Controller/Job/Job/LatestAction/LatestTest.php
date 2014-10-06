<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\LatestAction;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class LatestTest extends BaseControllerJsonTestCase {
    
    public function testLatestActionForPublicUser() {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $canonicalUrl = 'http://example.com';
        $jobId = $this->createJobAndGetId($canonicalUrl);

        $response = $this->getJobController('latestAction')->latestAction($canonicalUrl);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals($jobId, $this->getJobIdFromUrl($response->getTargetUrl()));
    }


    public function testLatestActionForDifferentUsers() {
        $canonicalUrl1 = 'http://one.example.com/';
        $canonicalUrl2 = 'http://two.example.com/';

        $user1 = $this->createAndActivateUser('user1@example.com', 'password1');
        $user2 = $this->createAndActivateUser('user2@example.com', 'password1');

        $jobId1 = $this->createJobAndGetId($canonicalUrl1, $user1->getEmail());
        $jobId2 = $this->createJobAndGetId($canonicalUrl2, $user2->getEmail());
        $jobId3 = $this->createJobAndGetId($canonicalUrl1);

        $this->getUserService()->setUser($user1);
        $response1 = $this->getJobController('latestAction', array(
            'user' => $user1->getEmail()
        ))->latestAction($canonicalUrl1);

        $this->getUserService()->setUser($user2);
        $response2 = $this->getJobController('latestAction', array(
            'user' => $user2->getEmail()
        ))->latestAction($canonicalUrl2);

        $response3 = $this->getJobController('latestAction')->latestAction($canonicalUrl1);

        $this->assertEquals(302, $response1->getStatusCode());
        $this->assertEquals(302, $response2->getStatusCode());
        $this->assertEquals(302, $response3->getStatusCode());

        $this->assertEquals($jobId1, $this->getJobIdFromUrl($response1->getTargetUrl()));
        $this->assertEquals($jobId2, $this->getJobIdFromUrl($response2->getTargetUrl()));
        $this->assertEquals($jobId3, $this->getJobIdFromUrl($response3->getTargetUrl()));
    }


    public function testLatestActionReturns404ForNoLatestJob() {
        $canonicalUrl = 'http://example.com';

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $response = $this->getJobController('latestAction')->latestAction($canonicalUrl);
        $this->assertEquals(404, $response->getStatusCode());
    }


    public function testForMemberInTeamWhereLatestBelongsToLeader() {
        $leader = $this->createAndActivateUser('leader@example.com');
        $member = $this->createAndActivateUser('member@example.com');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $this->getTeamMemberService()->add($team, $member);

        $canonicalUrl = 'http://example.com';
        $jobId = $this->createJobAndGetId($canonicalUrl, $leader->getEmail());

        $this->getUserService()->setUser($member);
        $response = $this->getJobController('latestAction')->latestAction($canonicalUrl);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals($jobId, $this->getJobIdFromUrl($response->getTargetUrl()));
    }


    public function testForLeaderInTeamMemberLatestBelongsToMember() {
        $leader = $this->createAndActivateUser('leader@example.com');
        $member = $this->createAndActivateUser('member@example.com');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $this->getTeamMemberService()->add($team, $member);

        $canonicalUrl = 'http://example.com';
        $jobId = $this->createJobAndGetId($canonicalUrl, $member->getEmail());

        $this->getUserService()->setUser($leader);
        $response = $this->getJobController('latestAction')->latestAction($canonicalUrl);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals($jobId, $this->getJobIdFromUrl($response->getTargetUrl()));
    }


    public function testForMemberInTeamWhereLatestBelongsToDifferentMember() {
        $leader = $this->createAndActivateUser('leader@example.com');
        $member1 = $this->createAndActivateUser('member1@example.com');
        $member2 = $this->createAndActivateUser('member2@example.com');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $this->getTeamMemberService()->add($team, $member1);
        $this->getTeamMemberService()->add($team, $member2);

        $canonicalUrl = 'http://example.com';
        $jobId = $this->createJobAndGetId($canonicalUrl, $member1->getEmail());

        $this->getUserService()->setUser($member2);
        $response = $this->getJobController('latestAction')->latestAction($canonicalUrl);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals($jobId, $this->getJobIdFromUrl($response->getTargetUrl()));
    }
    
}


