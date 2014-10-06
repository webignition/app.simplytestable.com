<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job\PrepareCommand\NoUrlsDiscovered;

use SimplyTestable\ApiBundle\Tests\Command\Job\PrepareCommand\CommandTest;

class PublicUserTest extends CommandTest {

    protected function preCall() {
        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 404',
            'HTTP/1.0 404',
            'HTTP/1.0 404',
            'HTTP/1.0 404',
            'HTTP/1.0 404'
        )));
    }

    protected function getJob() {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        return $this->getJobService()->getById($this->createAndResolveDefaultJob());
    }

    protected function getExpectedReturnCode() {
        return 0;
    }


    public function testResqueJobIsNotCreated() {
        $this->assertTrue($this->getResqueQueueService()->isEmpty('task-assign-collection'));
    }
}
