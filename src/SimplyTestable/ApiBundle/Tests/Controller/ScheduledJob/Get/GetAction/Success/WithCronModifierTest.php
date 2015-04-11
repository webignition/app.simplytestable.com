<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Get\GetAction\Success;

class WithCronModifierTest extends SuccessTest {

    protected function getCronModifier() {
        return 'foo';
    }

    /**
     * @return bool
     */
    protected function isExpectingCronModifier()
    {
        return true;
    }
}