<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\StatusAction;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class OwnersTest extends BaseControllerJsonTestCase {
    
    protected function getActionName() {
        return 'statusAction';
    }


    public function testPublicJob() {
        $canonicalUrl = 'http://example.com/';

        $jobId = $this->createJobAndGetId($canonicalUrl);

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $response = $this->getJobController('statusAction')->statusAction($canonicalUrl, $jobId);
        $responseJsonObject = json_decode($response->getContent());

        $this->assertEquals([
            'public'
        ], $responseJsonObject->owners);
    }

    public function testPrivateJob() {
        $user = $this->createAndActivateUser('user@example.com');

        $canonicalUrl = 'http://example.com/';

        $jobId = $this->createJobAndGetId($canonicalUrl, $user->getUsername());

        $this->getUserService()->setUser($user);
        $responseJsonObject = json_decode($this->getJobController('statusAction')->statusAction($canonicalUrl, $jobId)->getContent());

        $this->assertEquals([
            'user@example.com'
        ], $responseJsonObject->owners);
    }


    public function testTeamJob() {
        $leader = $this->createAndActivateUser('leader@example.com');
        $user = $this->createAndActivateUser('user@example.com');

        $team = $this->getTeamService()->create('Foo', $leader);
        $this->getTeamMemberService()->add($team, $user);

        $canonicalUrl = 'http://example.com/';

        $jobId = $this->createJobAndGetId($canonicalUrl, $leader->getUsername());

        $this->getUserService()->setUser($user);
        $responseJsonObject = json_decode($this->getJobController('statusAction', [
            'user' => $user->getUsername()
        ])->statusAction($canonicalUrl, $jobId)->getContent());

        $this->assertEquals([
            'leader@example.com',
            'user@example.com'
        ], $responseJsonObject->owners);
    }
}