<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\LatestAction;

use SimplyTestable\ApiBundle\Tests\Controller\RoutingTest as BaseRoutingTest;

class RoutingTest extends BaseRoutingTest {
    
    
    /**
     * 
     * @return array
     */
    protected function getRouteParameters() {
        return array(
            'site_root_url' => 'http://example.com/'
        );
    }

}