<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Update\UpdateAction\MissingInput;

class TypeTest extends MissingInputTest {

    protected function getMissingRequestValueKey() {
        return 'type';
    }
}