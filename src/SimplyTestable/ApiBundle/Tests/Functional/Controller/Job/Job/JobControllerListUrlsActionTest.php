<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job;

use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;

class JobControllerListUrlsActionTest extends AbstractJobControllerTest
{
    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userFactory = new UserFactory($this->container);
        $this->jobFactory = new JobFactory($this->container);
    }

    public function testRequest()
    {
        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => 'http://example.com',
        ]);

        $this->getCrawler([
            'url' => $this->container->get('router')->generate('job_job_listurls', [
                'test_id' => $job->getId(),
                'site_root_url' => $job->getWebsite()->getCanonicalUrl(),
            ])
        ]);

        /* @var RedirectResponse $response */
        $response = $this->getClientResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @dataProvider accessDataProvider
     *
     * @param string $owner
     * @param string $requester
     * @param bool $callSetPublic
     * @param int $expectedResponseStatusCode
     */
    public function testListUrlsAction($owner, $requester, $callSetPublic, $expectedResponseStatusCode)
    {
        $users = $this->userFactory->createPublicAndPrivateUserSet();

        $ownerUser = $users[$owner];
        $requesterUser = $users[$requester];

        $this->getUserService()->setUser($ownerUser);
        $canonicalUrl = 'http://example.com/';

        $job = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
            JobFactory::KEY_USER => $ownerUser,
        ]);

        if ($callSetPublic) {
            $this->jobController->setPublicAction($canonicalUrl, $job->getId());
        }

        $this->getUserService()->setUser($requesterUser);

        $response = $this->jobController->listUrlsAction($canonicalUrl, $job->getId());

        $this->assertEquals($expectedResponseStatusCode, $response->getStatusCode());

        if ($expectedResponseStatusCode === 200) {
            $expectedUrls = [];

            foreach ($job->getTasks() as $task) {
                /* @var Task $task */
                $expectedUrls[] = [
                    'url' => $task->getUrl(),
                ];
            }

            $responseData = json_decode($response->getContent(), 200);
            $this->assertEquals($expectedUrls, $responseData);
        }
    }

    /**
     * @return array
     */
    public function accessDataProvider()
    {
        return [
            'public owner, public requester' => [
                'owner' => 'public',
                'requester' => 'public',
                'callSetPublic' => false,
                'expectedStatusCode' => 200,
            ],
            'public owner, private requester' => [
                'owner' => 'public',
                'requester' => 'private',
                'callSetPublic' => false,
                'expectedStatusCode' => 200,
            ],
            'private owner, private requester' => [
                'owner' => 'private',
                'requester' => 'private',
                'callSetPublic' => false,
                'expectedStatusCode' => 200,
            ],
            'private owner, public requester' => [
                'owner' => 'private',
                'requester' => 'public',
                'callSetPublic' => false,
                'expectedStatusCode' => 403,
            ],
            'private owner, public requester, public test' => [
                'owner' => 'private',
                'requester' => 'public',
                'callSetPublic' => true,
                'expectedStatusCode' => 200,
            ],
        ];
    }
}
