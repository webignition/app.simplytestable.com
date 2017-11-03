<?php

namespace Tests\ApiBundle\Functional\Controller\Job\Job;

use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;

class JobControllerSetPrivateActionTest extends AbstractJobControllerTest
{
    const CANONICAL_URL = 'http://example.com/';

    public function testRequest()
    {
        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => 'http://example.com',
        ]);

        $this->getCrawler([
            'url' => $this->container->get('router')->generate('job_job_setprivate', [
                'test_id' => $job->getId(),
                'site_root_url' => $job->getWebsite()->getCanonicalUrl(),
            ])
        ]);

        /* @var RedirectResponse $response */
        $response = $this->getClientResponse();

        $this->assertEquals(302, $response->getStatusCode());
    }

    /**
     * @dataProvider setPrivateActionDataProvider
     *
     * @param string $owner
     * @param string $requester
     * @param bool $callSetPublic
     * @param int $expectedResponseStatusCode
     * @param bool $expectedIsPublic
     */
    public function testSetPrivateAction(
        $owner,
        $requester,
        $callSetPublic,
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

        if ($callSetPublic) {
            $this->jobController->setPublicAction($job->getWebsite()->getCanonicalUrl(), $job->getId());
        }

        $this->setUser($requesterUser);
        $response = $this->jobController->setPrivateAction($job->getWebsite()->getCanonicalUrl(), $job->getId());

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
    public function setPrivateActionDataProvider()
    {
        return [
            'public owner, public requester' => [
                'owner' => 'public',
                'requester' => 'public',
                'callSetPublic' => false,
                'expectedStatusCode' => 302,
                'expectedIsPublic' => true,
            ],
            'public owner, private requester' => [
                'owner' => 'public',
                'requester' => 'private',
                'callSetPublic' => false,
                'expectedStatusCode' => 302,
                'expectedIsPublic' => true,
            ],
            'private owner, private requester, private test' => [
                'owner' => 'private',
                'requester' => 'private',
                'callSetPublic' => false,
                'expectedStatusCode' => 302,
                'expectedIsPublic' => false,
            ],
            'private owner, private requester, public test' => [
                'owner' => 'private',
                'requester' => 'private',
                'callSetPublic' => true,
                'expectedStatusCode' => 302,
                'expectedIsPublic' => false,
            ],
            'private owner, public requester, private test' => [
                'owner' => 'private',
                'requester' => 'public',
                'callSetPublic' => false,
                'expectedStatusCode' => 302,
                'expectedIsPublic' => false,
            ],
            'private owner, public requester, public test' => [
                'owner' => 'private',
                'requester' => 'public',
                'callSetPublic' => true,
                'expectedStatusCode' => 302,
                'expectedIsPublic' => true,
            ],
            'private owner, different private requester, private test' => [
                'owner' => 'private',
                'requester' => 'leader',
                'callSetPublic' => false,
                'expectedStatusCode' => 403,
                'expectedIsPublic' => false,
            ],
        ];
    }
}
