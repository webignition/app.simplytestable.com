<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Create\Uniqueness\ForUser;

use SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Create\Uniqueness\ServiceTest;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;

class WithSameTaskConfigurationsTest extends ServiceTest {

    const LABEL = 'foo';
    const PARAMETERS = 'parameters';

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

    /**
     * @var ConfigurationValues
     */
    private $values;

    public function setUp() {
        parent::setUp();

        $taskConfigurationCollection = new TaskConfigurationCollection();

        foreach ($this->taskTypeOptionsSet as $taskTypeName => $taskTypeOptions) {
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
        $this->values->setParameters(self::PARAMETERS);

        $this->getJobConfigurationService()->setUser($this->getUserService()->getPublicUser());
        $this->getJobConfigurationService()->create($this->values);
    }


    public function testCreateWithSameArgumentsAndSameTaskConfigurationsThrowsException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception',
            'Matching configuration already exists',
            JobConfigurationServiceException::CODE_CONFIGURATION_ALREADY_EXISTS
        );

        $this->values->setLabel('bar');

        $this->getJobConfigurationService()->create($this->values);
    }


}