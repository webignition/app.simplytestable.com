<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Create\CreateAction\MissingInput;

class TypeTest extends MissingInputTest {

    protected function getMissingRequestValueKey() {
        return 'type';
    }
}