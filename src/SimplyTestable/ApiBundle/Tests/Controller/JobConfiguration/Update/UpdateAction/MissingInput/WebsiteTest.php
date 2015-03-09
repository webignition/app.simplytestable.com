<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Update\UpdateAction\MissingInput;

class WebsiteTest extends MissingInputTest {

    protected function getMissingRequestValueKey() {
        return 'website';
    }
}