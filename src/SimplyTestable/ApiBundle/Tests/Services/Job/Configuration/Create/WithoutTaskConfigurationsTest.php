<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Create;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

class WithoutTaskConfigurationsTest extends ServiceTest {

    const LABEL = 'foo';

    /**
     * @var JobConfiguration
     */
    private $jobConfiguration = null;

    public function setUp() {
        parent::setUp();

        $this->jobConfiguration = $this->getJobConfigurationService()->create(
            $this->getUserService()->getPublicUser(),
            $this->getWebSiteService()->fetch('http://example.com/'),
            $this->getJobTypeService()->getFullSiteType(),
            [],
            self::LABEL,
            ''
        );
    }

    public function testIdIsSet() {
        $this->assertNotNull($this->jobConfiguration->getId());
    }

}