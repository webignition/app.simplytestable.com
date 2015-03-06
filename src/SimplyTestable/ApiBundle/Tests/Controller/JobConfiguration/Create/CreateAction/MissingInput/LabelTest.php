<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Create\CreateAction\MissingInput;

class LabelTest extends MissingInputTest {

    protected function getMissingRequestValueKey() {
        return 'label';
    }
}