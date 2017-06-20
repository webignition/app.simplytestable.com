<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\ScheduledJob\ExecuteCommand\Success;

use SimplyTestable\ApiBundle\Command\ScheduledJob\ExecuteCommand;

class WithTaskTypesDisabledTest extends SuccessTest {

    /**
     * @return array
     */
    protected function getCreateJobConfigurationArray() {
        $returnValues = parent::getCreateJobConfigurationArray();

        $returnValues['task_configuration']['HTML validation'] = [
            'is-enabled' => false
        ];

        return $returnValues;
    }

    protected function getExpectedReturnCode() {
        return ExecuteCommand::RETURN_CODE_OK;
    }

    public function testResultantJobTaskTypeCollectionCount() {
        $this->assertEquals(1, $this->latestJob->getTaskTypeCollection()->count());
    }
}
