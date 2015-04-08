<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\ScheduledJob\Persist;

class WithCronModifierTest extends PersistTest {

    protected function getScheduledJob() {
        $scheduledJob =  parent::getScheduledJob();
        $scheduledJob->setCronModifier('foo');
        return $scheduledJob;
    }

}
