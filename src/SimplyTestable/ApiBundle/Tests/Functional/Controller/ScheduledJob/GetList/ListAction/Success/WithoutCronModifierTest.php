<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\GetList\ListAction\Success;

class WithoutCronModifierTest extends SuccessTest {

    /**
     * @return string|null
     */
    protected function getCronModifier()
    {
        return null;
    }
}