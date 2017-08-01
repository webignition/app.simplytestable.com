<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Update\Success;

use SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Update\ServiceTest;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;

abstract class SuccessTest extends ServiceTest {

    const LABEL = 'bar';

    /**
     * @var JobConfiguration
     */
    private $jobConfiguration;


    abstract protected function getCurrentUser();

    protected function setUp() {
        parent::setUp();

        $this->preCreateJobConfigurations();

        $originalValues = new ConfigurationValues();
        $originalValues->setLabel(self::LABEL);
        $originalValues->setParameters($this->getOriginalParameters());
        $originalValues->setTaskConfigurationCollection($this->getStandardTaskConfigurationCollection());
        $originalValues->setType($this->getOriginalJobType());
        $originalValues->setWebsite($this->getOriginalWebsite());

        $this->getJobConfigurationService()->setUser($this->getCurrentUser());
        $this->jobConfiguration = $this->getJobConfigurationService()->create($originalValues);

        $newValues = new ConfigurationValues();
        $newValues->setLabel($this->getNewLabel());
        $newValues->setParameters($this->getNewParameters());
        $newValues->setTaskConfigurationCollection($this->getNewTaskConfigurationCollection());
        $newValues->setType($this->getNewJobType());
        $newValues->setWebsite($this->getNewWebsite());

        $this->getJobConfigurationService()->update(
            $this->jobConfiguration,
            $newValues
        );

        $jobConfigurationId = $this->jobConfiguration->getId();

        $this->getManager()->clear();
        $this->jobConfiguration = $this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\Job\Configuration')->find($jobConfigurationId);
    }

    abstract protected function getOriginalWebsite();
    abstract protected function getOriginalJobType();
    abstract protected function getOriginalParameters();
    abstract protected function getNewWebsite();
    abstract protected function getNewJobType();
    abstract protected function getNewParameters();
    abstract protected function getNewLabel();

    /**
     * @return TaskConfigurationCollection
     */
    abstract protected function getNewTaskConfigurationCollection();

    protected function preCreateJobConfigurations() {

    }

    public function testJobConfigurationHasNewWebsite() {
        $this->assertEquals($this->jobConfiguration->getWebsite(), $this->getNewWebsite());
    }


    public function testJobConfigurationHasNewJobType() {
        $this->assertEquals($this->jobConfiguration->getType(), $this->getNewJobType());
    }


    public function testJobConfigurationHasNewParameters() {
        $this->assertEquals($this->jobConfiguration->getParameters(), $this->getNewParameters());
    }


    public function testJobConfigurationHasNewTaskConfigurationCollection() {
        foreach ($this->jobConfiguration->getTaskConfigurations() as $taskConfiguration) {
            $this->assertTrue($this->getNewTaskConfigurationCollection()->contains($taskConfiguration));
        }
    }


    public function testJobConfigurationHasNewLabel() {
        $this->assertEquals($this->jobConfiguration->getLabel(), $this->getNewLabel());
    }

}