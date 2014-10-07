<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job\PrepareCommand\NoUrlsDiscovered;

use SimplyTestable\ApiBundle\Tests\Command\Job\PrepareCommand\CommandTest;

class PrivateUserTest extends CommandTest {

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
        $user = $this->createAndActivateUser('user@example.com');
        $this->getUserService()->setUser($user);
        return $this->getJobService()->getById($this->createAndResolveJob(self::DEFAULT_CANONICAL_URL, $user->getUsername()));
    }

    protected function getExpectedReturnCode() {
        return 0;
    }

    public function testTaskAssignCollectionResqueJobForCrawlJobFirstTaskIsCreated() {
        $crawlJob = $this->getCrawlJobContainerService()->getForJob($this->job)->getCrawlJob();

        $this->assertTrue($this->getResqueQueueService()->contains(
            'task-assign-collection',
            ['ids' => $crawlJob->getTasks()->first()->getId()]
        ));
    }
}
