<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\ListAction;

use SimplyTestable\ApiBundle\Tests\Controller\RoutingTest as BaseRoutingTest;

class RoutingTest extends BaseRoutingTest {
    
    
    /**
     * 
     * @return array
     */
    protected function getRouteParameters() {
        return array(
            'limit' => 1,
            'offset' => 0
        );
    }

}