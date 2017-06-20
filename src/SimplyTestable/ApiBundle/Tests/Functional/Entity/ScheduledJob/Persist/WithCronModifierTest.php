<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\ScheduledJob\Persist;

class WithCronModifierTest extends PersistTest {

    protected function getScheduledJob() {
        $scheduledJob =  parent::getScheduledJob();
        $scheduledJob->setCronModifier('foo');
        return $scheduledJob;
    }

}
