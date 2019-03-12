<?php

namespace App\Tests\Functional\Controller\Job\Job;

use App\Entity\Job\Job;
use App\Services\JobService;
use App\Services\Team\Service as TeamService;
use App\Services\UserService;
use App\Services\WebSiteService;
use App\Tests\Services\JobFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Tests\Services\UserFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @group Controller/Job/JobController
 */
class JobControllerLatestActionTest extends AbstractJobControllerTest
{
    public function testRequest()
    {
        $job = $this->jobFactory->create(array(
            JobFactory::KEY_URL => 'http://example.com',
        ));

        $this->getCrawler([
            'url' => self::$container->get('router')->generate('job_job_latest', [
                'site_root_url' => $job->getWebsite()->getCanonicalUrl(),
            ])
        ]);

        /* @var RedirectResponse $response */
        $response = $this->getClientResponse();

        $this->assertTrue(
            $response->isRedirect(sprintf(
                'http://localhost/job/%d/',
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
        $jobService = self::$container->get(JobService::class);
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
            self::$container->get(WebSiteService::class),
            self::$container->get(UserService::class),
            self::$container->get(TeamService::class),
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
        $teamService = self::$container->get(TeamService::class);

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
            self::$container->get(WebSiteService::class),
            self::$container->get(UserService::class),
            self::$container->get(TeamService::class),
            $leader,
            'http://example.com'
        );
    }
}
