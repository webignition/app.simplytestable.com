<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Update\NonUniqueNewLabel;

use SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Update\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;

class ForUserTest extends ServiceTest {

    const LABEL = 'foo';

    /**
     * @var JobConfiguration
     */
    private $jobConfiguration;


    public function setUp() {
        parent::setUp();

        $values = new ConfigurationValues();
        $values->setWebsite($this->getWebSiteService()->fetch('http://example.com/'));
        $values->setType($this->getJobTypeService()->getFullSiteType());
        $values->setTaskConfigurationCollection($this->getStandardTaskConfigurationCollection());
        $values->setLabel(self::LABEL);

        $this->getJobConfigurationService()->setUser($this->getUserService()->getPublicUser());
        $this->jobConfiguration = $this->getJobConfigurationService()->create($values);
    }

    public function testUpdateWithNonUniqueNewLabelForUserThrowsException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception',
            'Label "' . self::LABEL . '" is not unique',
            JobConfigurationServiceException::CODE_LABEL_NOT_UNIQUE
        );

        $newValues = new ConfigurationValues();
        $newValues->setLabel(self::LABEL);

        $this->getJobConfigurationService()->update(
            $this->jobConfiguration,
            $newValues
        );
    }

}
