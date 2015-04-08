<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\ScheduledJob\Persist;

class WithoutCronModifierTest extends PersistTest {

    protected function getScheduledJob() {
        return parent::getScheduledJob();
    }

}
