<?php
namespace SimplyTestable\ApiBundle\Entity\Job;

use Doctrine\ORM\Mapping as ORM;

/**
 * 
 * @ORM\Entity
 * @ORM\Table(
 *     name="Job",
 *     indexes={
 *        @ORM\Index(name="user_index", columns={"user_id"}),
 *        @ORM\Index(name="website_index", columns={"website_id"}),
 *        @ORM\Index(name="state_index", columns={"state_id"})}
 * )
 */
class Job
{
    /**
     * 
     * @var integer
     * 
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     *
     * @var User
     * 
     * @ORM\Column(type="integer", nullable=false, name="user_id")
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\User")
     */
    protected $user;
    
    /**
     *
     * @var SimplyTestable\ApiBundle\Entity\WebSite
     * 
     * @ORM\Column(type="integer", nullable=false, name="website_id")
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\WebSite")
     */
    protected $website;
    
    
    /**
     *
     * @var SimplyTestable\ApiBundle\Entity\State
     * 
     * @ORM\Column(type="integer", nullable=false, name="state_id")
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\State")
     */
    protected $state;     
    
    /**
     *
     * @var \Doctrine\Common\Collections\Collection
     * 
     * @ORM\OneToMany(targetEntity="SimplyTestable\ApiBundle\Entity\Task\Task", mappedBy="job")
     */
    protected $tasks;
}