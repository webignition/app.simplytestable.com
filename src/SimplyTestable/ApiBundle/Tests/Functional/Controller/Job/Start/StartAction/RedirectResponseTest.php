<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Start\StartAction;

abstract class RedirectResponseTest extends SingleResponseTest {

    /**
     * @return int
     */
    protected function getExpectedResponseStatusCode()
    {
        return 302;
    }
}