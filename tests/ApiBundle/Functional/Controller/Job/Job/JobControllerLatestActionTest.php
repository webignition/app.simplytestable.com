<?php

namespace Tests\ApiBundle\Functional\Controller\Job\Job;

use SimplyTestable\ApiBundle\Services\JobService;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;

class JobControllerLatestActionTest extends AbstractJobControllerTest
{
    public function testRequest()
    {
        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => 'http://example.com',
        ]);

        $this->getCrawler([
            'url' => $this->container->get('router')->generate('job_job_latest', [
                'site_root_url' => $job->getWebsite()->getCanonicalUrl(),
            ])
        ]);

        /* @var RedirectResponse $response */
        $response = $this->getClientResponse();

        $this->assertTrue(
            $response->isRedirect(sprintf(
                'http://localhost/job/http://example.com//%d/',
                $job->getId()
            ))
        );
    }

    /**
     * @dataProvider latestActionDataProvider
     *
     * @param string $owner
     * @param string $requester
     * @param bool $callSetPublic
     * @param int $expectedResponseStatusCode
     */
    public function testLatestAction($owner, $requester, $callSetPublic, $expectedResponseStatusCode)
    {
        $jobService = $this->container->get(JobService::class);
        $users = $this->userFactory->createPublicPrivateAndTeamUserSet();

        $ownerUser = $users[$owner];
        $requesterUser = $users[$requester];

        $this->setUser($ownerUser);

        $jobStateNames = $jobService->getFinishedStateNames();

        foreach ($jobStateNames as $jobStateName) {
            $job = $this->jobFactory->create([
                JobFactory::KEY_USER => $ownerUser,
                JobFactory::KEY_STATE => $jobStateName,
            ]);
        }

        if ($callSetPublic) {
            $this->jobController->setPublicAction($job->getWebsite()->getCanonicalUrl(), $job->getId());
        }

        $this->setUser($requesterUser);
        $response = $this->jobController->latestAction($job->getWebsite()->getCanonicalUrl());

        $this->assertEquals($expectedResponseStatusCode, $response->getStatusCode());

        if ($expectedResponseStatusCode === 302) {
            $jobFromResponse = $this->jobFactory->getFromResponse($response);
            $this->assertEquals($job->getId(), $jobFromResponse->getId());
        }
    }

    /**
     * @return array
     */
    public function latestActionDataProvider()
    {
        return [
            'public owner, public requester' => [
                'owner' => 'public',
                'requester' => 'public',
                'callSetPublic' => false,
                'expectedStatusCode' => 302,
            ],
            'public owner, private requester' => [
                'owner' => 'public',
                'requester' => 'private',
                'callSetPublic' => false,
                'expectedStatusCode' => 302,
            ],
            'private owner, private requester' => [
                'owner' => 'private',
                'requester' => 'private',
                'callSetPublic' => false,
                'expectedStatusCode' => 302,
            ],
            'private owner, public requester, private test' => [
                'owner' => 'private',
                'requester' => 'public',
                'callSetPublic' => false,
                'expectedStatusCode' => 404,
            ],
            'private owner, public requester, public test' => [
                'owner' => 'private',
                'requester' => 'public',
                'callSetPublic' => true,
                'expectedStatusCode' => 404,
            ],
            'leader owner, leader requester' => [
                'owner' => 'leader',
                'requester' => 'leader',
                'callSetPublic' => false,
                'expectedStatusCode' => 302,
            ],
            'leader owner, member1 requester' => [
                'owner' => 'leader',
                'requester' => 'member1',
                'callSetPublic' => false,
                'expectedStatusCode' => 302,
            ],
        ];
    }

    public function testForLeaderInTeamWhereLatestTestDoesNotExist()
    {
        $teamService = $this->container->get('simplytestable.services.teamservice');

        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);

        $teamService->create(
            'Foo',
            $leader
        );

        $this->setUser($leader);
        $response = $this->jobController->latestAction('http://example.com');

        $this->assertEquals(404, $response->getStatusCode());
    }
}
