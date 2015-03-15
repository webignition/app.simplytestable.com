<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Create\CreateAction\MissingInput;

class JobConfigurationTest extends MissingInputTest {

    protected function getMissingRequestValueKey() {
        return 'job-configuration';
    }
}