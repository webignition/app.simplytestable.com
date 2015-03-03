<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Create\Uniqueness;

use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

class WithDifferentTaskConfigurationsTest extends ServiceTest {

    const LABEL = 'foo';
    const PARAMETERS = 'parameters';

    /**
     * @var JobConfiguration
     */
    private $jobConfiguration = null;

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

    public function setUp() {
        parent::setUp();

        /* @var $taskConfigurations TaskConfiguration[] */
        $taskConfigurations = [];

        foreach ($this->taskTypeOptionsSets[0] as $taskTypeName => $taskTypeOptions) {
            $taskConfiguration = new TaskConfiguration();
            $taskConfiguration->setType(
                $this->getTaskTypeService()->getByName($taskTypeName)
            );
            $taskConfiguration->setOptions($taskTypeOptions['options']);

            $taskConfigurations[] = $taskConfiguration;
        }

        $this->getJobConfigurationService()->setUser($this->getUserService()->getPublicUser());
        $this->jobConfiguration = $this->getJobConfigurationService()->create(
            $this->getWebSiteService()->fetch('http://example.com/'),
            $this->getJobTypeService()->getFullSiteType(),
            $taskConfigurations,
            self::LABEL,
            self::PARAMETERS
        );
    }


    public function testCreateWithSameArgumentsAndDifferentTaskConfigurationsDoesNotThrowException() {
        /* @var $taskConfigurations TaskConfiguration[] */
        $taskConfigurations = [];

        foreach ($this->taskTypeOptionsSets[1] as $taskTypeName => $taskTypeOptions) {
            $taskConfiguration = new TaskConfiguration();
            $taskConfiguration->setType(
                $this->getTaskTypeService()->getByName($taskTypeName)
            );
            $taskConfiguration->setOptions($taskTypeOptions['options']);

            $taskConfigurations[] = $taskConfiguration;
        }

        $this->getJobConfigurationService()->create(
            $this->getWebSiteService()->fetch('http://example.com/'),
            $this->getJobTypeService()->getFullSiteType(),
            $taskConfigurations,
            'bar',
            self::PARAMETERS
        );
    }


}