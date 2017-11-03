<?php

namespace Tests\ApiBundle\Functional\Entity\ScheduledJob\Persist;

class WithoutCronModifierTest extends PersistTest {

    protected function getScheduledJob() {
        return parent::getScheduledJob();
    }

}
