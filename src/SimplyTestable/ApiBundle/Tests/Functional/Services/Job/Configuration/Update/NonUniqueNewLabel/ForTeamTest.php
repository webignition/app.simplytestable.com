<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Update\NonUniqueNewLabel;

use SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Update\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

class ForTeamTest extends ServiceTest {

    const LABEL1 = 'foo';
    const LABEL2 = 'bar';

    /**
     * @var JobConfiguration
     */
    private $jobConfiguration;

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
        $values->setLabel(self::LABEL1);

        $this->getJobConfigurationService()->setUser($member);
        $this->jobConfiguration = $this->getJobConfigurationService()->create($values);

        $values->setLabel(self::LABEL2);
        $values->setWebsite($this->getWebSiteService()->fetch('http://example.com/bar'));
        $this->getJobConfigurationService()->create($values);

        $this->getJobConfigurationService()->setUser($leader);
    }

    public function testCreateWithNonUniqueLabelForTeamThrowsException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception',
            'Label "' . self::LABEL2 . '" is not unique',
            JobConfigurationServiceException::CODE_LABEL_NOT_UNIQUE
        );

        $newValues = new ConfigurationValues();
        $newValues->setLabel(self::LABEL2);

        $this->getJobConfigurationService()->update(
            $this->jobConfiguration,
            $newValues
        );
    }

}