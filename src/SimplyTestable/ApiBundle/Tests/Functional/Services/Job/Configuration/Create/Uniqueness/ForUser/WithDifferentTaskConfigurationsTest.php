<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Create\Uniqueness\ForUser;

use SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Create\Uniqueness\ServiceTest;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;

class WithDifferentTaskConfigurationsTest extends ServiceTest {

    const LABEL = 'foo';
    const PARAMETERS = 'parameters';

    private $taskTypeOptionsSets = [
        [
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
        ],
        [
            'HTML validation' => [
                'options' => [
                    'html+foo' => 'html+bar',
                    'html+foo++' => 'html+bar++'
                ]
            ],
            'CSS validation' => [
                'options' => [
                    'css+foo++' => 'css+bar++'
                ]
            ],
            'JS static analysis' => [
                'options' => []
            ],
            'Link integrity' => [
                'options' => [
                    'li+foo' => 'li+bar'
                ]
            ]
        ]
    ];

    /**
     * @var ConfigurationValues
     */
    private $values;

    public function setUp() {
        parent::setUp();

        $taskConfigurationCollection = new TaskConfigurationCollection();

        foreach ($this->taskTypeOptionsSets[0] as $taskTypeName => $taskTypeOptions) {
            $taskConfiguration = new TaskConfiguration();
            $taskConfiguration->setType(
                $this->getTaskTypeService()->getByName($taskTypeName)
            );
            $taskConfiguration->setOptions($taskTypeOptions['options']);

            $taskConfigurationCollection->add($taskConfiguration);
        }

        $this->values = new ConfigurationValues();
        $this->values->setLabel(self::LABEL);
        $this->values->setTaskConfigurationCollection($taskConfigurationCollection);
        $this->values->setType($this->getJobTypeService()->getFullSiteType());
        $this->values->setWebsite($this->getWebSiteService()->fetch('http://example.com/'));

        $this->getJobConfigurationService()->setUser($this->getUserService()->getPublicUser());
        $this->getJobConfigurationService()->create($this->values);
    }


    public function testCreateWithSameArgumentsAndDifferentTaskConfigurationsDoesNotThrowException() {
        $taskConfigurationCollection = new TaskConfigurationCollection();

        foreach ($this->taskTypeOptionsSets[1] as $taskTypeName => $taskTypeOptions) {
            $taskConfiguration = new TaskConfiguration();
            $taskConfiguration->setType(
                $this->getTaskTypeService()->getByName($taskTypeName)
            );
            $taskConfiguration->setOptions($taskTypeOptions['options']);

            $taskConfigurationCollection->add($taskConfiguration);
        }

        $this->values->setLabel('bar');
        $this->values->setTaskConfigurationCollection($taskConfigurationCollection);

        $this->getJobConfigurationService()->create($this->values);
    }


}