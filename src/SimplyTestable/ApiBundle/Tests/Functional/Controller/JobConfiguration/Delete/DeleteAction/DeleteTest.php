<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Delete\DeleteAction;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Delete\ActionTest;

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