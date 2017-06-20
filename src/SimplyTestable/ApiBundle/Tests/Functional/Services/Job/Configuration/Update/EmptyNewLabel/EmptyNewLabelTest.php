<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Update\EmptyNewLabel;

use SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Update\ServiceTest;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;

abstract class EmptyNewLabelTest extends ServiceTest {

    const LABEL = 'bar';

    /**
     * @var JobConfiguration
     */
    private $jobConfiguration = null;

    private $taskTypeOptionsSet = [
        'HTML validation' => [
            'options' => [
                'html+foo' => 'html+bar'
            ]
        ],
        'CSS validation' => [
            'options' => [
                'css+foo' => 'css+bar'
            ]
        ],
        'JS static analysis' => [
            'options' => [
                'js+foo' => 'js+bar'
            ]
        ],
        'Link integrity' => [
            'options' => [
                'li+foo' => 'li+bar'
            ]
        ]
    ];

    abstract protected function getCurrentUser();


    public function testEmptyLabelIsIgnored() {
        $taskConfigurationCollection = new TaskConfigurationCollection();

        foreach ($this->taskTypeOptionsSet as $taskTypeName => $taskTypeOptions) {
            $taskConfiguration = new TaskConfiguration();
            $taskConfiguration->setType(
                $this->getTaskTypeService()->getByName($taskTypeName)
            );
            $taskConfiguration->setOptions($taskTypeOptions['options']);

            $taskConfigurationCollection->add($taskConfiguration);
        }

        $values = new ConfigurationValues();
        $values->setLabel(self::LABEL);
        $values->setParameters('parameters');
        $values->setWebsite($this->getWebSiteService()->fetch('http://example.com/'));
        $values->setTaskConfigurationCollection($taskConfigurationCollection);
        $values->setType($this->getJobTypeService()->getFullSiteType());

        $this->getJobConfigurationService()->setUser($this->getCurrentUser());
        $this->jobConfiguration = $this->getJobConfigurationService()->create($values);

        $newValues = new ConfigurationValues();
        $newValues->setLabel('');
        $newValues->setParameters('foo');

        $this->getJobConfigurationService()->update(
            $this->jobConfiguration,
            $newValues
        );
    }

}