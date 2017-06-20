<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Create\CreateAction\MissingInput;

class TaskConfigurationTest extends MissingInputTest {

    protected function getMissingRequestValueKey() {
        return 'task-configuration';
    }
}