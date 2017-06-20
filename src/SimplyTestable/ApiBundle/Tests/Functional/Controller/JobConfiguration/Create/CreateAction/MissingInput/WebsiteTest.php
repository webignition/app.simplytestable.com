<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Create\CreateAction\MissingInput;

class WebsiteTest extends MissingInputTest {

    protected function getMissingRequestValueKey() {
        return 'website';
    }
}