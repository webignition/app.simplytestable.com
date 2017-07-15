<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Delete\DeleteAction;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\RoutingTest as BaseRoutingTest;

class RoutingTest extends BaseRoutingTest {

    public function setUp() {
        parent::setUp();
        $this->getRouter()->getContext()->setMethod('POST');
    }

    /**
     *
     * @return array
     */
    protected function getRouteParameters() {
        return [
            'id' => '1'
        ];
    }

}