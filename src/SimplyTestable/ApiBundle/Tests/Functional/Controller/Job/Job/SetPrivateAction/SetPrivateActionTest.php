<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\SetPrivateAction;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class SetPrivateActionTest extends BaseControllerJsonTestCase
{
    const CANONICAL_URL = 'http://example.com/';

    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobFactory = new JobFactory($this->container);
    }

    public function testSetPrivateByPublicUserForJobOwnedByPublicUser()
    {
        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => self::CANONICAL_URL,
        ]);

        $this->assertTrue($job->getIsPublic());

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $response = $this->getJobController('setPrivateAction')->setPrivateAction(self::CANONICAL_URL, $job->getId());

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($job->getIsPublic());
    }

    public function testSetPrivateByNonPublicUserForJobOwnedBySameNonPublicUser()
    {
        $user = $this->createAndActivateUser('user@example.com', 'password1');

        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => self::CANONICAL_URL,
            JobFactory::KEY_USER => $user,
        ]);

        $this->assertFalse($job->getIsPublic());

        $this->getUserService()->setUser($user);
        $response = $this->getJobController('setPrivateAction')->setPrivateAction(self::CANONICAL_URL, $job->getId());

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertFalse($job->getIsPublic());
    }

    public function testSetPrivateByNonPublicUserForJobOwnedByPublicUser()
    {
        $user = $this->createAndActivateUser('user@example.com', 'password1');

        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => self::CANONICAL_URL,
        ]);
        $this->assertTrue($job->getIsPublic());

        $this->getUserService()->setUser($user);
        $response = $this->getJobController('setPublicAction')->setPublicAction(self::CANONICAL_URL, $job->getId());

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($job->getIsPublic());
    }

    public function testSetPrivateByNonPublicUserForJobOwnedByDifferentNonPublicUser()
    {
        $user1 = $this->createAndActivateUser('user1@example.com', 'password1');
        $user2 = $this->createAndActivateUser('user2@example.com', 'password1');

        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => self::CANONICAL_URL,
            JobFactory::KEY_USER => $user1,
        ]);

        $this->assertFalse($job->getIsPublic());

        $this->getUserService()->setUser($user2);
        $response = $this->getJobController('setPrivateAction', array(
            'user' => $user2->getEmail()
        ))->setPrivateAction(self::CANONICAL_URL, $job->getId());

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($job->getIsPublic());
    }

    public function testSetPrivateByTeamLeaderForJobOwnedByTeamMember()
    {
        $leader = $this->createAndActivateUser('leader@example.com');
        $member = $this->createAndActivateUser('member@example.com');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $this->getTeamMemberService()->add($team, $member);

        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => self::CANONICAL_URL,
            JobFactory::KEY_USER => $member,
        ]);
        $this->assertFalse($job->getIsPublic());

        $this->getUserService()->setUser($leader);
        $response = $this->getJobController('setPrivateAction')->setPrivateAction(self::CANONICAL_URL, $job->getId());

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertFalse($job->getIsPublic());
    }

    public function testSetPrivateByTeamMemberForJobOwnedByTeamLeader()
    {
        $leader = $this->createAndActivateUser('leader@example.com');
        $member = $this->createAndActivateUser('member@example.com');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $this->getTeamMemberService()->add($team, $member);

        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => self::CANONICAL_URL,
            JobFactory::KEY_USER => $leader,
        ]);
        $this->assertFalse($job->getIsPublic());

        $this->getUserService()->setUser($member);
        $response = $this->getJobController('setPrivateAction')->setPrivateAction(self::CANONICAL_URL, $job->getId());

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertFalse($job->getIsPublic());
    }

    public function testSetPrivateByTeamMemberForJobOwnedByDifferentTeamMember()
    {
        $leader = $this->createAndActivateUser('leader@example.com');
        $member1 = $this->createAndActivateUser('member1@example.com');
        $member2 = $this->createAndActivateUser('member2@example.com');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $this->getTeamMemberService()->add($team, $member1);
        $this->getTeamMemberService()->add($team, $member2);

        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => self::CANONICAL_URL,
            JobFactory::KEY_USER => $member1,
        ]);
        $this->assertFalse($job->getIsPublic());

        $this->getUserService()->setUser($member2);
        $response = $this->getJobController('setPrivateAction')->setPrivateAction(self::CANONICAL_URL, $job->getId());

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertFalse($job->getIsPublic());
    }
}
