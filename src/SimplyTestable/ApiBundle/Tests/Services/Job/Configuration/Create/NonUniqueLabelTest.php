<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Create;

use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;

class NonUniqueLabelTest extends ServiceTest {

    const LABEL = 'foo';

    public function setUp() {
        parent::setUp();

        $this->getJobConfigurationService()->create(
            $this->getUserService()->getPublicUser(),
            $this->getWebSiteService()->fetch('http://example.com/'),
            $this->getJobTypeService()->getFullSiteType(),
            [],
            self::LABEL,
            ''
        );
    }

    public function testCreateWithNonUniqueLabelForUserThrowsException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception',
            'Label "' . self::LABEL . '" is not unique',
            JobConfigurationServiceException::CODE_LABEL_NOT_UNIQUE
        );

        $this->getJobConfigurationService()->create(
            $this->getUserService()->getPublicUser(),
            $this->getWebSiteService()->fetch('http://example.com/'),
            $this->getJobTypeService()->getFullSiteType(),
            [],
            self::LABEL,
            ''
        );
    }

}
