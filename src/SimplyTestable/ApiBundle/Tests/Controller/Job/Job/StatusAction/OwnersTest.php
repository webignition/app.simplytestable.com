<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\StatusAction;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class OwnersTest extends BaseControllerJsonTestCase
{
    protected function getActionName()
    {
        return 'statusAction';
    }

    public function testPublicJob()
    {
        $canonicalUrl = 'http://example.com/';
        $job = $this->createJobFactory()->create([
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
        $user = $this->createAndActivateUser('user@example.com');

        $canonicalUrl = 'http://example.com/';

        $job = $this->createJobFactory()->create([
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
        $leader = $this->createAndActivateUser('leader@example.com');
        $user = $this->createAndActivateUser('user@example.com');

        $team = $this->getTeamService()->create('Foo', $leader);
        $this->getTeamMemberService()->add($team, $user);

        $canonicalUrl = 'http://example.com/';

        $job = $this->createJobFactory()->create([
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
