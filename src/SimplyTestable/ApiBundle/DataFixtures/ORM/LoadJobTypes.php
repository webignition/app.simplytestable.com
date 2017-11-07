<?php

namespace SimplyTestable\ApiBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadJobTypes extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    private $jobTypes = array(
        'Full site' => array(
            'description' => 'Test the entirety of the site'
        ),
        'Single URL' => array(
            'description' => 'Test only the submitted URL'
        ),
        'crawl' => array(
            'description' => 'Crawl the site to find URLs for testing'
        )
    );

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
        $jobTypeRepository = $this->container->get('simplytestable.repository.jobtype');

        foreach ($this->jobTypes as $name => $properties) {
            $jobType = $jobTypeRepository->findOneByName($name);

            if (is_null($jobType)) {
                $jobType = new JobType();
            }

            $jobType->setDescription($properties['description']);
            $jobType->setName($name);

            $manager->persist($jobType);
            $manager->flush();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 5; // the order in which fixtures will be loaded
    }
}
