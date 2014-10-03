<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job\PrepareCommand;

class NoDiscoveredUrlsTest extends CommandTest {

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
        return $this->getJobService()->getById($this->createAndResolveDefaultJob());
    }

    protected function getExpectedReturnCode() {
        return 0;
    }
}
