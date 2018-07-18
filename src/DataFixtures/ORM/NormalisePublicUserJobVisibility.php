<?php

namespace App\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity\Job\Job;
use App\Entity\User;
use App\Services\UserService;

class NormalisePublicUserJobVisibility extends Fixture implements DependentFixtureInterface
{
    /**
     * @var User
     */
    private $publicUser;

    /**
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->publicUser = $userService->getPublicUser();
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $jobRepository = $manager->getRepository(Job::class);
        $publicUserPrivateJobs = $jobRepository->findBy(array(
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
