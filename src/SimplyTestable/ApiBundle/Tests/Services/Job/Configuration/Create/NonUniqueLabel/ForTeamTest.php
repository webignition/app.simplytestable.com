<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Create\NonUniqueLabel;

use SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Create\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;

class ForTeamTest extends ServiceTest {

    const LABEL = 'foo';

    public function setUp() {
        parent::setUp();

        $leader = $this->createAndActivateUser('leader@example.com', 'password');
        $member = $this->createAndActivateUser('user@example.com');

        $this->getTeamMemberService()->add($this->getTeamService()->create(
            'Foo',
            $leader
        ), $member);

        $this->getJobConfigurationService()->setUser($member);
        $this->getJobConfigurationService()->create(
            $this->getWebSiteService()->fetch('http://example.com/'),
            $this->getJobTypeService()->getFullSiteType(),
            $this->getStandardTaskConfigurationCollection(),
            self::LABEL,
            ''
        );

        $this->getJobConfigurationService()->setUser($leader);
    }

    public function testCreateWithNonUniqueLabelForTeamThrowsException() {
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
