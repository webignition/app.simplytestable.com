<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\TeamInvite\GetByTokenAction;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\RoutingTest as BaseRoutingTest;

class RoutingTest extends BaseRoutingTest {


    /**
     *
     * @return array
     */
    protected function getRouteParameters() {
        return [
            'token' => 'foo'
        ];
    }

}