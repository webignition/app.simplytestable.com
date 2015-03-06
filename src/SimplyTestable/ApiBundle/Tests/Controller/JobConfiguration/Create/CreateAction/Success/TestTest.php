<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Create\CreateAction\Success;

class TestTest extends SuccessTest {

    public function setUp() {
        parent::setUp();
    }

    protected function getLabel() {
        return 'foo';
    }

}