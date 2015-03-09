<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Update\UpdateAction\MissingInput;

class TaskConfigurationTest extends MissingInputTest {

    protected function getMissingRequestValueKey() {
        return 'task-configuration';
    }
}