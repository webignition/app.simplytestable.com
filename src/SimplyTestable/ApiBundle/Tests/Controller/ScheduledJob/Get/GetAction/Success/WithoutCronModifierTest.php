<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Get\GetAction\Success;

class WithoutCronModifierTest extends SuccessTest {

    protected function getCronModifier() {
        return null;
    }

    /**
     * @return bool
     */
    protected function isExpectingCronModifier()
    {
        return false;
    }

}