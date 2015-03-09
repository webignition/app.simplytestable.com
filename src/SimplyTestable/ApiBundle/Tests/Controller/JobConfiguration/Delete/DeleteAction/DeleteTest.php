<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Delete\DeleteAction;

use SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Create\ActionTest;

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
            'label' => self::LABEL
        ];
    }

}