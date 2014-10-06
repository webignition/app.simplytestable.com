<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job\PrepareCommand;

class MaintenanceModeTest extends CommandTest {

    protected function preCall() {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
    }

    protected function getJob() {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        return $this->getJobService()->getById($this->createAndResolveDefaultJob());
    }

    protected function getExpectedReturnCode() {
        return 2;
    }

    public function testResqueJobIsRequeued() {
        $this->assertTrue($this->getResqueQueueService()->contains('job-prepare', [
            'id' => $this->job->getId()
        ]));
    }
}
