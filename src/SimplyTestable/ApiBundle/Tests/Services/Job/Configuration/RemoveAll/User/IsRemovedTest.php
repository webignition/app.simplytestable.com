<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\RemoveAll\User;


class IsRemovedTest extends UserTest {

    public function setUp() {
        parent::setUp();
        $this->getJobConfigurationService()->removeAll();
    }

    public function testJobConfigurationIdIsNotSet() {
        $this->assertNull($this->jobConfiguration->getId());
    }

    public function testCannotBeRetrievedWithLabel() {
        $this->assertNull($this->getJobConfigurationService()->get(self::LABEL));
    }

    public function testJobTaskConfigurationsAreRemoved() {
        $this->assertEquals(0, $this->jobConfiguration->getTaskConfigurationsAsCollection()->count());
    }
}