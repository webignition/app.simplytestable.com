<?php

namespace App\DataFixtures\ORM;

use App\Repository\JobRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity\User;
use App\Services\UserService;

class NormalisePublicUserJobVisibility extends Fixture implements DependentFixtureInterface
{
    /**
     * @var User
     */
    private $publicUser;
    private $jobRepository;

    public function __construct(UserService $userService, JobRepository $jobRepository)
    {
        $this->publicUser = $userService->getPublicUser();
        $this->jobRepository = $jobRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $publicUserPrivateJobs = $this->jobRepository->findBy(array(
            'user' => $this->publicUser,
            'isPublic' => false
        ));

        if (!empty($publicUserPrivateJobs)) {
            foreach ($publicUserPrivateJobs as $job) {
                $job->setIsPublic(true);
                $manager->persist($job);
            }
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadUserData::class,
        ];
    }
}
