<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Update\UpdateAction\Success;

class TestTest extends SuccessTest {

    public function setUp() {
        parent::setUp();
    }

    protected function getLabel() {
        return 'foo';
    }

}