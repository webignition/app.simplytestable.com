<?php

namespace Tests\ApiBundle\Functional\Controller\Job\Job;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use SimplyTestable\ApiBundle\Services\UserService;
use SimplyTestable\ApiBundle\Services\WebSiteService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @group Controller/Job/JobController
 */
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
     * @param bool $expectedIsNotFound
     */
    public function testLatestAction(
        $owner,
        $requester,
        $callSetPublic,
        $expectedIsNotFound
    ) {
        $jobService = $this->container->get(JobService::class);
        $users = $this->userFactory->createPublicPrivateAndTeamUserSet();

        $ownerUser = $users[$owner];
        $requesterUser = $users[$requester];

        /* @var Job $job */
        $job = null;

        $this->setUser($ownerUser);

        $jobStateNames = $jobService->getFinishedStateNames();

        foreach ($jobStateNames as $jobStateName) {
            $job = $this->jobFactory->create([
                JobFactory::KEY_USER => $ownerUser,
                JobFactory::KEY_STATE => $jobStateName,
            ]);
        }

        if ($callSetPublic) {
            $this->callSetPublicAction($ownerUser, $job);
        }

        $this->setUser($requesterUser);

        if ($expectedIsNotFound) {
            $this->expectException(NotFoundHttpException::class);
        }

        $response = $this->jobController->latestAction(
            $this->container->get(WebSiteService::class),
            $this->container->get(UserService::class),
            $this->container->get(TeamService::class),
            $requesterUser,
            $job->getWebsite()->getCanonicalUrl()
        );

        $this->assertTrue($response->isRedirect());

        $jobFromResponse = $this->jobFactory->getFromResponse($response);
        $this->assertEquals($job->getId(), $jobFromResponse->getId());
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
                'expectIsNotFound' => false,
            ],
            'public owner, private requester' => [
                'owner' => 'public',
                'requester' => 'private',
                'callSetPublic' => false,
                'expectIsNotFound' => false,
            ],
            'private owner, private requester' => [
                'owner' => 'private',
                'requester' => 'private',
                'callSetPublic' => false,
                'expectIsNotFound' => false,
            ],
            'private owner, public requester, private test' => [
                'owner' => 'private',
                'requester' => 'public',
                'callSetPublic' => false,
                'expectIsNotFound' => true,
            ],
            'private owner, public requester, public test' => [
                'owner' => 'private',
                'requester' => 'public',
                'callSetPublic' => true,
                'expectIsNotFound' => true,
            ],
            'leader owner, leader requester' => [
                'owner' => 'leader',
                'requester' => 'leader',
                'callSetPublic' => false,
                'expectIsNotFound' => false,
            ],
            'leader owner, member1 requester' => [
                'owner' => 'leader',
                'requester' => 'member1',
                'callSetPublic' => false,
                'expectIsNotFound' => false,
            ],
        ];
    }

    public function testForLeaderInTeamWhereLatestTestDoesNotExist()
    {
        $teamService = $this->container->get(TeamService::class);

        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);

        $teamService->create(
            'Foo',
            $leader
        );

        $this->setUser($leader);

        $this->expectException(NotFoundHttpException::class);

        $this->jobController->latestAction(
            $this->container->get(WebSiteService::class),
            $this->container->get(UserService::class),
            $this->container->get(TeamService::class),
            $leader,
            'http://example.com'
        );
    }
}
