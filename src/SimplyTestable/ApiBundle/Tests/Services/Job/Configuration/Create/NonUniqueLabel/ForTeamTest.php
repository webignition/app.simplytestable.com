<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Create\NonUniqueLabel;

use SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Create\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;

class ForTeamTest extends ServiceTest {

    const LABEL = 'foo';

    private $leader;

    public function setUp() {
        parent::setUp();

        $this->leader = $this->createAndActivateUser('leader@example.com', 'password');

        $team = $this->getTeamService()->create(
            'Foo',
            $this->leader
        );

        $member = $this->createAndActivateUser('user@example.com');

        $this->getTeamMemberService()->add($team, $member);

        $this->getJobConfigurationService()->setUser($member);
        $this->getJobConfigurationService()->create(
            $this->getWebSiteService()->fetch('http://example.com/'),
            $this->getJobTypeService()->getFullSiteType(),
            [],
            self::LABEL,
            ''
        );
    }

    public function testCreateWithNonUniqueLabelForTeamThrowsException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception',
            'Label "' . self::LABEL . '" is not unique',
            JobConfigurationServiceException::CODE_LABEL_NOT_UNIQUE
        );

        $this->getJobConfigurationService()->setUser($this->leader);
        $this->getJobConfigurationService()->create(
            $this->getWebSiteService()->fetch('http://example.com/'),
            $this->getJobTypeService()->getFullSiteType(),
            [],
            self::LABEL,
            ''
        );
    }

}
