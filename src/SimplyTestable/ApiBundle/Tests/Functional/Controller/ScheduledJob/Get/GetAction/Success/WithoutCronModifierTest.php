<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Get\GetAction\Success;

class WithoutCronModifierTest extends SuccessTest {

    protected function getCronModifier() {
        return null;
    }

}