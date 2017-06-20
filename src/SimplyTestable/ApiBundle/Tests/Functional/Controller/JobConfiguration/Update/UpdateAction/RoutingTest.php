<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Update\UpdateAction;

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
            'label' => 'foo'
        ];
    }


    protected function getRequestPostData() {
        return [
            'website' => 'http://example.com/',
            'type' => 'Full site',
            'task-configuration' => [
                'HTML validation' => [],
                'CSS validation' => []
            ],
            'parameters' => '',
            'label' => 'foo'
        ];
    }

}