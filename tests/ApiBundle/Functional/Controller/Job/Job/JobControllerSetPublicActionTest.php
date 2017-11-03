<?php

namespace Tests\ApiBundle\Functional\Controller\Job\Job;

use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;

class JobControllerSetPublicActionTest extends AbstractJobControllerTest
{
    const CANONICAL_URL = 'http://example.com/';

    public function testRequest()
    {
        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => 'http://example.com',
        ]);

        $this->getCrawler([
            'url' => $this->container->get('router')->generate('job_job_setpublic', [
                'test_id' => $job->getId(),
                'site_root_url' => $job->getWebsite()->getCanonicalUrl(),
            ])
        ]);

        /* @var RedirectResponse $response */
        $response = $this->getClientResponse();

        $this->assertEquals(302, $response->getStatusCode());
    }

    /**
     * @dataProvider setPublicActionDataProvider
     *
     * @param string $owner
     * @param string $requester
     * @param int $expectedResponseStatusCode
     * @param bool $expectedIsPublic
     */
    public function testSetPublicAction(
        $owner,
        $requester,
        $expectedResponseStatusCode,
        $expectedIsPublic
    ) {
        $users = $this->userFactory->createPublicPrivateAndTeamUserSet();

        $ownerUser = $users[$owner];
        $requesterUser = $users[$requester];

        $this->setUser($ownerUser);

        $job = $this->jobFactory->create([
            JobFactory::KEY_USER => $ownerUser,
        ]);

        $this->setUser($requesterUser);
        $response = $this->jobController->setPublicAction($job->getWebsite()->getCanonicalUrl(), $job->getId());

        $this->assertEquals($expectedResponseStatusCode, $response->getStatusCode());

        if ($expectedResponseStatusCode === 302) {
            $jobFromResponse = $this->jobFactory->getFromResponse($response);
            $this->assertEquals($job->getId(), $jobFromResponse->getId());
        }

        $this->assertEquals($expectedIsPublic, $job->getIsPublic());
    }

    /**
     * @return array
     */
    public function setPublicActionDataProvider()
    {
        return [
            'public owner, public requester' => [
                'owner' => 'public',
                'requester' => 'public',
                'expectedStatusCode' => 302,
                'expectedIsPublic' => true,
            ],
            'public owner, private requester' => [
                'owner' => 'public',
                'requester' => 'private',
                'expectedStatusCode' => 302,
                'expectedIsPublic' => true,
            ],
            'private owner, private requester' => [
                'owner' => 'private',
                'requester' => 'private',
                'expectedStatusCode' => 302,
                'expectedIsPublic' => true,
            ],
            'private owner, public requester' => [
                'owner' => 'private',
                'requester' => 'public',
                'expectedStatusCode' => 302,
                'expectedIsPublic' => false,
            ],
            'private owner, different private requester' => [
                'owner' => 'private',
                'requester' => 'leader',
                'expectedStatusCode' => 403,
                'expectedIsPublic' => false,
            ],
            'leader owner, member1 requester' => [
                'owner' => 'leader',
                'requester' => 'member1',
                'expectedStatusCode' => 302,
                'expectedIsPublic' => true,
            ],
            'member1 owner, leader requester' => [
                'owner' => 'member1',
                'requester' => 'leader',
                'expectedStatusCode' => 302,
                'expectedIsPublic' => true,
            ],
            'member1 owner, member2 requester' => [
                'owner' => 'member1',
                'requester' => 'member2',
                'expectedStatusCode' => 302,
                'expectedIsPublic' => true,
            ],
        ];
    }
}
