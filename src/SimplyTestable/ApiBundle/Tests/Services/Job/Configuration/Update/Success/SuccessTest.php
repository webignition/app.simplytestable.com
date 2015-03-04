<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Update\Success;

use SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Update\ServiceTest;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;

abstract class SuccessTest extends ServiceTest {

    const LABEL = 'bar';

    /**
     * @var JobConfiguration
     */
    private $jobConfiguration;

    /**
     * @var int
     */
    private $updateReturnValue;

    abstract protected function getCurrentUser();

    public function setUp() {
        parent::setUp();

        $this->preCreateJobConfigurations();

        $this->getJobConfigurationService()->setUser($this->getCurrentUser());
        $this->jobConfiguration = $this->getJobConfigurationService()->create(
            $this->getOriginalWebsite(),
            $this->getOriginalJobType(),
            $this->getStandardTaskConfigurationCollection(),
            self::LABEL,
            $this->getOriginalParameters()
        );

        $this->updateReturnValue = $this->getJobConfigurationService()->update(
            self::LABEL,
            $this->getNewWebsite(),
            $this->getNewJobType(),
            $this->getNewTaskConfigurationCollection(),
            $this->getNewParameters()
        );

        $this->jobConfiguration = $this->getJobConfigurationService()->get(self::LABEL);
    }

    abstract protected function getOriginalWebsite();
    abstract protected function getOriginalJobType();
    abstract protected function getOriginalParameters();
    abstract protected function getNewWebsite();
    abstract protected function getNewJobType();
    abstract protected function getNewParameters();

    /**
     * @return TaskConfigurationCollection
     */
    abstract protected function getNewTaskConfigurationCollection();

    protected function preCreateJobConfigurations() {

    }

    public function testUpdateIsSuccessful() {
        $this->assertTrue($this->updateReturnValue);
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

}