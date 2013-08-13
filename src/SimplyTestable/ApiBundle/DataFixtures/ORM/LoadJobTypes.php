<?php

namespace SimplyTestable\ApiBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use SimplyTestable\ApiBundle\Entity\Job\Type;

class LoadJobTypes extends AbstractFixture implements OrderedFixtureInterface
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
        $jobTypeRepository = $manager->getRepository('SimplyTestable\ApiBundle\Entity\Job\Type');        
        
        foreach ($this->jobTypes as $name => $properties) {            
            $jobType = $jobTypeRepository->findOneByName($name);
            
            if (is_null($jobType)) {
                $jobType = new Type();
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
