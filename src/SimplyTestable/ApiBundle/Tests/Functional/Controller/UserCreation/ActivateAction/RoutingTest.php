<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\UserCreation\ActivateAction;

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