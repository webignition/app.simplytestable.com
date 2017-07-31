<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\StatusAction;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class OwnersTest extends BaseControllerJsonTestCase
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

    protected function getActionName()
    {
        return 'statusAction';
    }

    public function testPublicJob()
    {
        $canonicalUrl = 'http://example.com/';
        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
        ]);

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $response = $this->getJobController('statusAction')->statusAction($canonicalUrl, $job->getId());
        $responseJsonObject = json_decode($response->getContent());

        $this->assertEquals([
            'public'
        ], $responseJsonObject->owners);
    }

    public function testPrivateJob()
    {
        $user = $this->userFactory->createAndActivateUser();

        $canonicalUrl = 'http://example.com/';

        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
            JobFactory::KEY_USER => $user,
        ]);

        $this->getUserService()->setUser($user);
        $responseJsonObject = json_decode(
            $this->getJobController('statusAction')->statusAction($canonicalUrl, $job->getId())->getContent()
        );

        $this->assertEquals([
            'user@example.com'
        ], $responseJsonObject->owners);
    }

    public function testTeamJob()
    {
        $leader = $this->userFactory->createAndActivateUser('leader@example.com');
        $user = $this->userFactory->createAndActivateUser('user@example.com');

        $team = $this->getTeamService()->create('Foo', $leader);
        $this->getTeamMemberService()->add($team, $user);

        $canonicalUrl = 'http://example.com/';

        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
            JobFactory::KEY_USER => $leader,
        ]);

        $this->getUserService()->setUser($user);
        $responseJsonObject = json_decode($this->getJobController('statusAction', [
            'user' => $user->getUsername()
        ])->statusAction($canonicalUrl, $job->getId())->getContent());

        $this->assertEquals([
            'leader@example.com',
            'user@example.com'
        ], $responseJsonObject->owners);
    }
}
