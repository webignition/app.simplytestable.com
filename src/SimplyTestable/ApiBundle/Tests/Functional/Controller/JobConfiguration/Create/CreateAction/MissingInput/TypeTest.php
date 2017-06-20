<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Create\CreateAction\MissingInput;

class TypeTest extends MissingInputTest {

    protected function getMissingRequestValueKey() {
        return 'type';
    }
}