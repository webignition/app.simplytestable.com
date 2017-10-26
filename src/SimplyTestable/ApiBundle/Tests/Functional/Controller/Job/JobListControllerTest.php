<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job;

use SimplyTestable\ApiBundle\Controller\Job\JobListController;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

class JobListControllerTest extends BaseSimplyTestableTestCase
{
    /**
     * @var User[]
     */
    private $users;

    /**
     * @var JobListController
     */
    private $jobListController;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $userFactory = new UserFactory($this->container);
        $this->users = $userFactory->createPublicPrivateAndTeamUserSet();

        $this->jobListController = new JobListController();
        $this->jobListController->setContainer($this->container);

        $this->requestStack = $this->container->get('request_stack');
    }

    public function testCountActionGetRequest()
    {
        $requestUrl = $this->container->get('router')->generate('job_joblist_count');

        $this->getCrawler([
            'url' => $requestUrl,
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertInstanceOf(JsonResponse::class, $response);

        $responseData = json_decode($response->getContent());

        $this->assertInternalType('int', $responseData);
    }

    public function testListActionGetRequest()
    {
        $requestUrl = $this->container->get('router')->generate(
            'job_joblist_list',
            [
                'limit' => 0,
                'offset' => 0,
            ]
        );

        $this->getCrawler([
            'url' => $requestUrl,
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertInstanceOf(JsonResponse::class, $response);

        $responseData = json_decode($response->getContent(), true);

        $this->assertInternalType('array', $responseData);
        $this->assertEquals([
            'max_results',
            'limit',
            'offset',
            'jobs',
        ], array_keys($responseData));

        $responseJobs = $responseData['jobs'];

        $this->assertInternalType('array', $responseJobs);
    }

    public function testWebsitesActionGetRequest()
    {
        $this->getCrawler([
            'url' => $this->container->get('router')->generate('job_joblist_websites'),
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertInstanceOf(JsonResponse::class, $response);

        $responseData = json_decode($response->getContent(), true);

        $this->assertInternalType('array', $responseData);
    }
}
