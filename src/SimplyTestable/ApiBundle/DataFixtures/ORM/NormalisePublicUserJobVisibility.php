<?php

namespace SimplyTestable\ApiBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class NormalisePublicUserJobVisibility extends AbstractFixture implements
    OrderedFixtureInterface,
    ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }


    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $jobRepository = $manager->getRepository(Job::class);
        $publicUserPrivateJobs = $jobRepository->findBy(array(
            'user' => $this->container->get('simplytestable.services.userservice')->getPublicUser(),
            'isPublic' => false
        ));

        if (count($publicUserPrivateJobs) === 0) {
            return true;
        }

        foreach ($publicUserPrivateJobs as $job) {
            $job->setIsPublic(true);
            $manager->persist($job);
        }

        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 9; // the order in which fixtures will be loaded
    }
}
