<?php

namespace SimplyTestable\ApiBundle\Tests\Resque\Job;

use SimplyTestable\ApiBundle\Resque\Job\CommandJob;

abstract class CommandJobTest extends JobTest {

    abstract protected function getJobCommandClass();

    public function testJobCommandInstanceType() {
        $this->assertInstanceOf($this->getJobCommandClass(), $this->getJob()->getCommand());
    }


    /**
     * @return CommandJob
     */
    protected function getJob() {
        return parent::getJob();
    }


}
