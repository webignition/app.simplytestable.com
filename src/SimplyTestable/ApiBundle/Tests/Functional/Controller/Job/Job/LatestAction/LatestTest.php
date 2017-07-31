<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\LatestAction;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class LatestTest extends BaseControllerJsonTestCase
{
    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobFactory = new JobFactory($this->container);
        $this->userFactory = new UserFactory($this->container);
    }

    public function testLatestActionForPublicUser()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $canonicalUrl = 'http://example.com';
        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
        ]);

        $response = $this->getJobController('latestAction')->latestAction($canonicalUrl);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals($job->getId(), $this->getJobIdFromUrl($response->getTargetUrl()));
    }

    public function testLatestActionForDifferentUsers()
    {
        $canonicalUrl1 = 'http://one.example.com/';
        $canonicalUrl2 = 'http://two.example.com/';

        $user1 = $this->userFactory->createAndActivateUser('user1@example.com');
        $user2 = $this->userFactory->createAndActivateUser('user2@example.com');

        $job1 = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl1,
            JobFactory::KEY_USER => $user1,
        ]);
        $job2 = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl2,
            JobFactory::KEY_USER => $user2,
        ]);
        $job3 = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl1,
        ]);


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

        $this->assertEquals($job1->getId(), $this->getJobIdFromUrl($response1->getTargetUrl()));
        $this->assertEquals($job2->getId(), $this->getJobIdFromUrl($response2->getTargetUrl()));
        $this->assertEquals($job3->getId(), $this->getJobIdFromUrl($response3->getTargetUrl()));
    }

    public function testLatestActionReturns404ForNoLatestJob()
    {
        $canonicalUrl = 'http://example.com';

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $response = $this->getJobController('latestAction')->latestAction($canonicalUrl);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testForMemberInTeamWhereLatestBelongsToLeader()
    {
        $leader = $this->userFactory->createAndActivateUser('leader@example.com');
        $member = $this->userFactory->createAndActivateUser('member@example.com');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $this->getTeamMemberService()->add($team, $member);

        $canonicalUrl = 'http://example.com';
        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
            JobFactory::KEY_USER => $leader,
        ]);

        $this->getUserService()->setUser($member);
        $response = $this->getJobController('latestAction')->latestAction($canonicalUrl);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals($job->getId(), $this->getJobIdFromUrl($response->getTargetUrl()));
    }

    public function testForLeaderInTeamMemberLatestBelongsToMember()
    {
        $leader = $this->userFactory->createAndActivateUser('leader@example.com');
        $member = $this->userFactory->createAndActivateUser('member@example.com');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $this->getTeamMemberService()->add($team, $member);

        $canonicalUrl = 'http://example.com';
        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
            JobFactory::KEY_USER => $member,
        ]);

        $this->getUserService()->setUser($leader);
        $response = $this->getJobController('latestAction')->latestAction($canonicalUrl);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals($job->getId(), $this->getJobIdFromUrl($response->getTargetUrl()));
    }

    public function testForMemberInTeamWhereLatestBelongsToDifferentMember()
    {
        $leader = $this->userFactory->createAndActivateUser('leader@example.com');
        $member1 = $this->userFactory->createAndActivateUser('member1@example.com');
        $member2 = $this->userFactory->createAndActivateUser('member2@example.com');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $this->getTeamMemberService()->add($team, $member1);
        $this->getTeamMemberService()->add($team, $member2);

        $canonicalUrl = 'http://example.com';
        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
            JobFactory::KEY_USER => $member1,
        ]);

        $this->getUserService()->setUser($member2);
        $response = $this->getJobController('latestAction')->latestAction($canonicalUrl);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals($job->getId(), $this->getJobIdFromUrl($response->getTargetUrl()));
    }
}
