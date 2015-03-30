<?php

namespace SimplyTestable\ApiBundle\Tests\Command\ScheduledJob\ExecuteCommand\Success;

class MatchingCurrentJobTest extends SuccessTest {

    protected function preCall() {
        parent::preCall();

        $this->executeCommand($this->getCommandName(), [
            'id' => $this->getScheduledJobId()
        ]);
    }


    public function testOnlyOneJobIsCreated() {
        $this->assertEquals(1, count($this->getJobService()->getEntityRepository()->findAll()));
    }


}
