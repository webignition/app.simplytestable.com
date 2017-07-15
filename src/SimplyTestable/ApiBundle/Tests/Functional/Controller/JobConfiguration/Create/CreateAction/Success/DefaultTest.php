<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Create\CreateAction\Success;

class DefaultTest extends SuccessTest {

    protected function getLabel() {
        return 'foo';
    }

    /**
     * @return bool
     */
    protected function getExpectedTaskConfigurationIsEnabled()
    {
        return true;
    }
}