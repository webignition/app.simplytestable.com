<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Controller\Job\Job;

use App\Tests\Services\JobFactory;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @group Controller/Job/JobController
 */
class JobControllerIsAuthorisedActionTest extends AbstractJobControllerTest
{
    private $users;

    protected function setUp()
    {
        parent::setUp();

        $this->users = $this->userFactory->createPublicAndPrivateUserSet();
    }

    /**
     * @dataProvider requestDataProvider
     */
    public function testRequest(string $ownerName, string $userName, bool $expectedIsAuthorised)
    {
        $owner = $this->users[$ownerName];
        $user = $this->users[$userName];

        $job = $this->jobFactory->create([
            JobFactory::KEY_USER => $owner,
        ]);

        $this->getCrawler([
            'url' => self::$container->get('router')->generate('job_job_isauthorised', [
                'test_id' => $job->getId(),
            ]),
            'user' => $user,
        ]);

        /* @var JsonResponse $response */
        $response = $this->getClientResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertInstanceOf(JsonResponse::class, $response);

        $decodedResponseData = json_decode($response->getContent());
        $this->assertEquals($expectedIsAuthorised, $decodedResponseData);
    }

    public function requestDataProvider(): array
    {
        return [
            'owner=public, user=public' => [
                'ownerName' => 'public',
                'userName' => 'public',
                'expectedIsAuthorised' => true,
            ],
            'owner=public, user=private' => [
                'ownerName' => 'public',
                'userName' => 'private',
                'expectedIsAuthorised' => true,
            ],
            'owner=private, user=private' => [
                'ownerName' => 'private',
                'userName' => 'private',
                'expectedIsAuthorised' => true,
            ],
            'owner=private, user=public' => [
                'ownerName' => 'private',
                'userName' => 'public',
                'expectedIsAuthorised' => false,
            ],
        ];
    }
}
