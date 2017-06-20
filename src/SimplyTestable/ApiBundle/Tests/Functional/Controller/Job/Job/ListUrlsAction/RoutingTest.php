<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\ListUrlsAction;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\RoutingTest as BaseRoutingTest;

class RoutingTest extends BaseRoutingTest {


    /**
     *
     * @return array
     */
    protected function getRouteParameters() {
        return array(
            'site_root_url' => 'http://example.com/',
            'test_id' => 1
        );
    }

}