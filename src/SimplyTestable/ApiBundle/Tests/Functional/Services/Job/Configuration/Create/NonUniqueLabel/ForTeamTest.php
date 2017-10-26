<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Create\NonUniqueLabel;

use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Create\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;

class ForTeamTest extends ServiceTest
{
    const LABEL = 'foo';

    /**
     * @var ConfigurationValues
     */
    private $values;

    protected function setUp()
    {
        parent::setUp();

        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');
        $websiteService = $this->container->get('simplytestable.services.websiteservice');
        $teamMemberService = $this->container->get('simplytestable.services.teammemberservice');

        $fullSiteJobType = $jobTypeService->getByName(JobTypeService::FULL_SITE_NAME);

        $userFactory = new UserFactory($this->container);

        $leader = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $member = $userFactory->createAndActivateUser();

        $teamMemberService->add($this->getTeamService()->create(
            'Foo',
            $leader
        ), $member);

        $this->getJobConfigurationService()->setUser($member);

        $this->values = new ConfigurationValues();
        $this->values->setLabel(self::LABEL);
        $this->values->setTaskConfigurationCollection($this->getStandardTaskConfigurationCollection());
        $this->values->setType($fullSiteJobType);
        $this->values->setWebsite($websiteService->fetch('http://example.com/'));

        $this->getJobConfigurationService()->create($this->values);

        $this->getJobConfigurationService()->setUser($leader);
    }

    public function testCreateWithNonUniqueLabelForTeamThrowsException()
    {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception',
            'Label "' . self::LABEL . '" is not unique',
            JobConfigurationServiceException::CODE_LABEL_NOT_UNIQUE
        );

        $this->getJobConfigurationService()->create($this->values);
    }
}
