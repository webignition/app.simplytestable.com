<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Get\GetAction;

use SimplyTestable\ApiBundle\Tests\Controller\RoutingTest as BaseRoutingTest;

class RoutingTest extends BaseRoutingTest {
    
    /**
     * 
     * @return array
     */
    protected function getRouteParameters() {
        return [
            'id' => 1
        ];
    }

}