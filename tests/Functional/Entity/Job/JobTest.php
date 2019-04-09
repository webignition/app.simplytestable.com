<?php

namespace App\Tests\Functional\Entity\Job;

use App\Entity\Job\Job;
use App\Entity\Job\Type;
use App\Entity\State;
use App\Entity\User;
use App\Entity\WebSite;
use App\Services\JobTypeService;
use App\Services\StateService;
use App\Services\UserService;
use App\Services\WebSiteService;
use Doctrine\ORM\EntityManagerInterface;
use App\Tests\Functional\AbstractBaseTestCase;

class JobTest extends AbstractBaseTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var User
     */
    private $user;

    /**
     * @var WebSite
     */
    private $website;

    /**
     * @var Type
     */
    private $jobType;

    /**
     * @var State
     */
    private $state;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $userService = self::$container->get(UserService::class);
        $websiteService = self::$container->get(WebSiteService::class);
        $jobTypeService = self::$container->get(JobTypeService::class);
        $stateService = self::$container->get(StateService::class);

        $this->entityManager = self::$container->get(EntityManagerInterface::class);
        $this->user = $userService->getPublicUser();
        $this->website = $websiteService->get('http://example.com/');
        $this->jobType = $jobTypeService->getFullSiteType();
        $this->state = $stateService->get(Job::STATE_STARTING);
    }

    public function testAllowsMultipleNullIdentifiers()
    {
        $job1 = Job::create($this->user, $this->website, $this->jobType, $this->state, '');
        $this->assertNull($job1->getId());

        $this->entityManager->persist($job1);
        $this->entityManager->flush();

        $this->assertNotNull($job1->getId());

        $job2 = Job::create($this->user, $this->website, $this->jobType, $this->state, '');
        $this->assertNull($job2->getId());

        $this->entityManager->persist($job2);
        $this->entityManager->flush();

        $this->assertNotNull($job2->getId());
    }
}
