<?php
namespace SimplyTestable\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Worker
{
    /**
     * 
     * @var type integer
     * 
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     *
     * @var string 
     * 
     * @ORM\Column(type="string", unique=true, nullable=false)
     */
    protected $url;
    
    
    /**
     *
     * @var \Doctrine\Common\Collections\Collection
     * 
     * @ORM\OneToMany(targetEntity="SimplyTestable\ApiBundle\Entity\Task\Task", mappedBy="worker")
     */
    protected $tasks;
}