<?php

namespace Tests\ApiBundle\Functional\Controller\Job\Job;

use Tests\ApiBundle\Factory\JobFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;

class JobControllerTaskIdsActionTest extends AbstractJobControllerTest
{
    public function testRequest()
    {
        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => 'http://example.com',
        ]);

        $this->getCrawler([
            'url' => $this->container->get('router')->generate('job_job_taskids', [
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
    public function testAccess($owner, $requester, $callSetPublic, $expectedResponseStatusCode)
    {
        $users = $this->userFactory->createPublicAndPrivateUserSet();

        $ownerUser = $users[$owner];
        $requesterUser = $users[$requester];

        $this->setUser($ownerUser);
        $canonicalUrl = 'http://example.com/';

        $job = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
            JobFactory::KEY_USER => $ownerUser,
        ]);

        if ($callSetPublic) {
            $this->jobController->setPublicAction($canonicalUrl, $job->getId());
        }

        $this->setUser($requesterUser);

        $response = $this->jobController->taskIdsAction($canonicalUrl, $job->getId());

        $this->assertEquals($expectedResponseStatusCode, $response->getStatusCode());

        if ($expectedResponseStatusCode === 200) {
            $expectedTaskIds = [];
            foreach ($job->getTasks() as $task) {
                $expectedTaskIds[] = $task->getId();
            }

            $responseData = json_decode($response->getContent(), 200);
            $this->assertEquals($expectedTaskIds, $responseData);
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
