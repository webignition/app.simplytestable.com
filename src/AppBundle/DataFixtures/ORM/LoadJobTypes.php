<?php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\Job\Type as JobType;
use AppBundle\Entity\Job\Type;

class LoadJobTypes extends Fixture
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
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $jobTypeRepository = $manager->getRepository(Type::class);

        foreach ($this->jobTypes as $name => $properties) {
            $jobType = $jobTypeRepository->findOneBy([
                'name' => $name,
            ]);

            if (is_null($jobType)) {
                $jobType = new JobType();
            }

            $jobType->setDescription($properties['description']);
            $jobType->setName($name);

            $manager->persist($jobType);
            $manager->flush();
        }
    }
}
