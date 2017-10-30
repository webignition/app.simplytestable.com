<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Update\NonUniqueNewLabel;

use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
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

    protected function setUp() {
        parent::setUp();

        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');
        $websiteService = $this->container->get('simplytestable.services.websiteservice');
        $teamMemberService = $this->container->get('simplytestable.services.teammemberservice');
        $teamService = $this->container->get('simplytestable.services.teamservice');

        $fullSiteJobType = $jobTypeService->getByName(JobTypeService::FULL_SITE_NAME);

        $userFactory = new UserFactory($this->container);

        $leader = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $member = $userFactory->createAndActivateUser();

        $teamMemberService->add($teamService->create(
            'Foo',
            $leader
        ), $member);

        $values = new ConfigurationValues();
        $values->setWebsite($websiteService->fetch('http://example.com/'));
        $values->setType($fullSiteJobType);
        $values->setTaskConfigurationCollection($this->getStandardTaskConfigurationCollection());
        $values->setLabel(self::LABEL1);

        $this->setUser($member);
        $this->jobConfiguration = $this->getJobConfigurationService()->create($values);

        $values->setLabel(self::LABEL2);
        $values->setWebsite($websiteService->fetch('http://example.com/bar'));
        $this->getJobConfigurationService()->create($values);

        $this->setUser($leader);
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
