<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Create\CreateAction\Success;

class DefaultTest extends SuccessTest {

    protected function getLabel() {
        return 'foo';
    }

    /**
     * @return bool
     */
    protected function getExpectedTaskConfigurationIsEnabled()
    {
        return false;
    }
}