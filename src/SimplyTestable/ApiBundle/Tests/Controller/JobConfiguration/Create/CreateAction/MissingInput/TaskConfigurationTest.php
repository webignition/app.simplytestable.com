<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Create\CreateAction\MissingInput;

class TaskConfigurationTest extends MissingInputTest {

    protected function getMissingRequestValueKey() {
        return 'task-configuration';
    }
}