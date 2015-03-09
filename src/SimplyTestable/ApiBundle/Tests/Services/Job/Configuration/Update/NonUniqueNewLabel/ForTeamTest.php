<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Update\NonUniqueNewLabel;

use SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Update\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;

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

        $values = new ConfigurationValues();
        $values->setWebsite($this->getWebSiteService()->fetch('http://example.com/'));
        $values->setType($this->getJobTypeService()->getFullSiteType());
        $values->setTaskConfigurationCollection($this->getStandardTaskConfigurationCollection());
        $values->setLabel(self::LABEL);

        $this->getJobConfigurationService()->setUser($member);
        $this->getJobConfigurationService()->create($values);
        $this->getJobConfigurationService()->setUser($leader);
    }

    public function testCreateWithNonUniqueLabelForTeamThrowsException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception',
            'Label "' . self::LABEL . '" is not unique',
            JobConfigurationServiceException::CODE_LABEL_NOT_UNIQUE
        );

        $values = new ConfigurationValues();
        $values->setLabel(self::LABEL);


        $this->getJobConfigurationService()->create($values);
    }

}
