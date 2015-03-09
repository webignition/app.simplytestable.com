<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Get\GetAction;

use SimplyTestable\ApiBundle\Tests\Controller\RoutingTest as BaseRoutingTest;

class RoutingTest extends BaseRoutingTest {
    
    /**
     * 
     * @return array
     */
    protected function getRouteParameters() {
        return [
            'label' => 'foo'
        ];
    }

}