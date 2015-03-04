<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Create\NonUniqueLabel;

use SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Create\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;

class ForUserTest extends ServiceTest {

    const LABEL = 'foo';

    public function setUp() {
        parent::setUp();

        $this->getJobConfigurationService()->setUser($this->getUserService()->getPublicUser());
        $this->getJobConfigurationService()->create(
            $this->getWebSiteService()->fetch('http://example.com/'),
            $this->getJobTypeService()->getFullSiteType(),
            $this->getStandardTaskConfigurationCollection(),
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
            $this->getWebSiteService()->fetch('http://example.com/'),
            $this->getJobTypeService()->getFullSiteType(),
            $this->getStandardTaskConfigurationCollection(),
            self::LABEL,
            ''
        );
    }

}
