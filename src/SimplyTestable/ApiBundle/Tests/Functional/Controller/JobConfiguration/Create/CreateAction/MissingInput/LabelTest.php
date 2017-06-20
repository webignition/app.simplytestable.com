<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Create\CreateAction\MissingInput;

class LabelTest extends MissingInputTest {

    protected function getMissingRequestValueKey() {
        return 'label';
    }
}