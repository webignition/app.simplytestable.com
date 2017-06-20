<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Create\NonUniqueLabel;

use SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Create\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;

class ForTeamTest extends ServiceTest {

    const LABEL = 'foo';

    /**
     * @var ConfigurationValues
     */
    private $values;

    public function setUp() {
        parent::setUp();

        $leader = $this->createAndActivateUser('leader@example.com', 'password');
        $member = $this->createAndActivateUser('user@example.com');

        $this->getTeamMemberService()->add($this->getTeamService()->create(
            'Foo',
            $leader
        ), $member);

        $this->getJobConfigurationService()->setUser($member);

        $this->values = new ConfigurationValues();
        $this->values->setLabel(self::LABEL);
        $this->values->setTaskConfigurationCollection($this->getStandardTaskConfigurationCollection());
        $this->values->setType($this->getJobTypeService()->getFullSiteType());
        $this->values->setWebsite($this->getWebSiteService()->fetch('http://example.com/'));

        $this->getJobConfigurationService()->create($this->values);

        $this->getJobConfigurationService()->setUser($leader);
    }

    public function testCreateWithNonUniqueLabelForTeamThrowsException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception',
            'Label "' . self::LABEL . '" is not unique',
            JobConfigurationServiceException::CODE_LABEL_NOT_UNIQUE
        );

        $this->getJobConfigurationService()->create($this->values);
    }

}
