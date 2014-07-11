<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Team\RemoveAction;

use SimplyTestable\ApiBundle\Tests\Controller\RoutingTest as BaseRoutingTest;

class RoutingTest extends BaseRoutingTest {
    
    
    /**
     *
     * @return array
     */
    protected function getRouteParameters() {
        return [
            'member_email' => 'user@example.com'
        ];
    }

}