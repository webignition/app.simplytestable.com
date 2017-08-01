<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Update\NonUniqueNewLabel;

use SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Update\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;

class ForUserTest extends ServiceTest {

    const LABEL1 = 'foo';
    const LABEL2 = 'bar';

    /**
     * @var JobConfiguration
     */
    private $jobConfiguration;


    protected function setUp() {
        parent::setUp();

        $values = new ConfigurationValues();
        $values->setWebsite($this->getWebSiteService()->fetch('http://example.com/'));
        $values->setType($this->getJobTypeService()->getFullSiteType());
        $values->setTaskConfigurationCollection($this->getStandardTaskConfigurationCollection());
        $values->setLabel(self::LABEL1);

        $this->getJobConfigurationService()->setUser($this->getUserService()->getPublicUser());
        $this->jobConfiguration = $this->getJobConfigurationService()->create($values);

        $values->setLabel(self::LABEL2);
        $values->setWebsite($this->getWebSiteService()->fetch('http://example.com/bar'));
        $jobConfiguration = $this->getJobConfigurationService()->create($values);
    }

    public function testUpdateWithNonUniqueNewLabelForUserThrowsException() {
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
