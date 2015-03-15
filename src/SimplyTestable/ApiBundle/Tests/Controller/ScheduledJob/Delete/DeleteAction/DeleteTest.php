<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Delete\DeleteAction;

use SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Delete\ActionTest;

abstract class DeleteTest extends ActionTest {

    const LABEL = 'foo';

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
            'id' => 1
        ];
    }

}