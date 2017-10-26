<?php

namespace SimplyTestable\ApiBundle\Tests\Unit\Services;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Request\Job\ListRequest;
use SimplyTestable\ApiBundle\Services\JobListConfigurationFactory;
use SimplyTestable\ApiBundle\Tests\Factory\ModelFactory;

class JobListConfigurationFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var JobListConfigurationFactory
     */
    private $jobListConfigurationFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobListConfigurationFactory = new JobListConfigurationFactory();
    }

    /**
     * @dataProvider createFromJobListRequestDataProvider
     *
     * @param ListRequest $listRequest
     * @param array $expectedTypesToExclude
     * @param array $expectedStatesToExclude
     * @param string $expectedUrlFilter
     * @param int[] $expectedJobIdsToExclude
     * @param int[] $expectedJobIdsToInclude
     * @param User $expectedUser
     */
    public function testCreateFromJobListRequest(
        ListRequest $listRequest,
        $expectedTypesToExclude,
        $expectedStatesToExclude,
        $expectedUrlFilter,
        $expectedJobIdsToExclude,
        $expectedJobIdsToInclude,
        User $expectedUser
    ) {
        $configuration = $this->jobListConfigurationFactory->createFromJobListRequest($listRequest);

        $this->assertEquals($expectedTypesToExclude, $configuration->getTypesToExclude());
        $this->assertEquals($expectedStatesToExclude, $configuration->getStatesToExclude());
        $this->assertEquals($expectedUrlFilter, $configuration->getUrlFilter());
        $this->assertEquals($expectedJobIdsToExclude, $configuration->getJobIdsToExclude());
        $this->assertEquals($expectedJobIdsToInclude, $configuration->getJobIdsToInclude());
        $this->assertEquals($expectedUser, $configuration->getUser());
    }

    /**
     * @return array
     */
    public function createFromJobListRequestDataProvider()
    {
        $user = ModelFactory::createUser([
            ModelFactory::USER_EMAIL => 'user@example.com',
        ]);

        $jobType = ModelFactory::createJobType([
            ModelFactory::JOB_TYPE_NAME => 'job-type-name',
        ]);

        $state = ModelFactory::createState('state-name');

        return [
            'empty' => [
                'listRequest' => ModelFactory::createJobListRequest([
                    ModelFactory::JOB_LIST_REQUEST_TYPES_TO_EXCLUDE => [],
                    ModelFactory::JOB_LIST_REQUEST_STATES_TO_EXCLUDE => [],

                    ModelFactory::JOB_LIST_REQUEST_URL_FILTER => [],
                    ModelFactory::JOB_LIST_REQUEST_JOB_IDS_TO_EXCLUDE => [],
                    ModelFactory::JOB_LIST_REQUEST_JOB_IDS_TO_INCLUDE => [],
                    ModelFactory::JOB_LIST_REQUEST_USER => $user,
                ]),
                'expectedTypesToExclude' => [],
                'expectedStatesToExclude' => [],
                'expectedUrlFilter' => [],
                'expectedJobIdsToExclude' => [],
                'expectedJobIdsToInclude' => [],
                'expectedUser' => $user,
            ],
            'non-empty' => [
                'listRequest' => ModelFactory::createJobListRequest([
                    ModelFactory::JOB_LIST_REQUEST_TYPES_TO_EXCLUDE => [$jobType],
                    ModelFactory::JOB_LIST_REQUEST_STATES_TO_EXCLUDE => [$state],
                    ModelFactory::JOB_LIST_REQUEST_URL_FILTER => 'foo',
                    ModelFactory::JOB_LIST_REQUEST_JOB_IDS_TO_EXCLUDE => [1,2,3],
                    ModelFactory::JOB_LIST_REQUEST_JOB_IDS_TO_INCLUDE => [4,5,6],
                    ModelFactory::JOB_LIST_REQUEST_USER => $user,
                ]),
                'expectedTypesToExclude' => [$jobType],
                'expectedStatesToExclude' => [$state],
                'expectedUrlFilter' => 'foo',
                'expectedJobIdsToExclude' => [1,2,3],
                'expectedJobIdsToInclude' => [4,5,6],
                'expectedUser' => $user,
            ],
        ];
    }
}
