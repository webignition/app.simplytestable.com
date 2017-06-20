<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Get\GetAction\Success;

class WithCronModifierTest extends SuccessTest {

    protected function getCronModifier() {
        return 'foo';
    }

}