<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Get;

use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;

class ForUserWithMatchTest extends ServiceTest {

    const LABEL = 'foo';

    /**
     * @var JobConfiguration
     */
    private $originalConfiguration = null;

    /**
     * @var JobConfiguration
     */
    private $retrievedConfiguration = null;

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

        $jobConfigurationValues = new ConfigurationValues();
        $jobConfigurationValues->setLabel(self::LABEL);
        $jobConfigurationValues->setTaskConfigurationCollection($taskConfigurationCollection);
        $jobConfigurationValues->setType($this->getJobTypeService()->getFullSiteType());
        $jobConfigurationValues->setWebsite($this->getWebSiteService()->fetch('http://example.com/'));
        $jobConfigurationValues->setParameters('parameters');

        $this->getJobConfigurationService()->setUser($this->getUserService()->getPublicUser());
        $this->originalConfiguration = $this->getJobConfigurationService()->create($jobConfigurationValues);

        $this->getManager()->clear();

        $this->retrievedConfiguration = $this->getJobConfigurationService()->get(self::LABEL);
    }

    public function testOriginalAndRetrievedAreNotTheExactSameObject() {
        $this->assertNotEquals(
            spl_object_hash($this->originalConfiguration),
            spl_object_hash($this->retrievedConfiguration)
        );
    }

    public function testOriginalAndRetrievedAreTheSameEntity() {
        $this->assertEquals($this->originalConfiguration->getId(), $this->retrievedConfiguration->getId());
    }

}
