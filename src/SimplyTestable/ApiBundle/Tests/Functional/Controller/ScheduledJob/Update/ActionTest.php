<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Update;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\ActionTest as BaseActionTest;

abstract class ActionTest extends BaseActionTest
{
    protected function setUp()
    {
        parent::setUp();
        $this->getRouter()->getContext()->setMethod('POST');
    }
}
