<?php
namespace SimplyTestable\ApiBundle\Entity\Task;

use Doctrine\ORM\Mapping as ORM;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;
use webignition\InternetMediaType\InternetMediaType;

/**
 * 
 * @ORM\Entity
 * @ORM\Table(
 *     name="TaskOutput"
 * )
 * 
 */
class Output
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
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $output;
    
    
    /**
     *
     * @var \webignition\InternetMediaType\InternetMediaType 
     * @ORM\Column(type="string", nullable=true)
     */
    protected $contentType;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    
    /**
     * Set output
     *
     * @param string $output
     * @return Task
     */
    public function setOutput($output)
    {
        $this->output = $output;
    
        return $this;
    }

    /**
     * Get output
     *
     * @return string 
     */
    public function getOutput()
    {
        return $this->output;
    } 
    
    
    /**
     *
     * @param InternetMediaType $contentType
     * @return \SimplyTestable\ApiBundle\Entity\Task\Output 
     */
    public function setContentType(InternetMediaType $contentType)
    {
        $this->contentType = $contentType;
        return $this;
    }
    
    
    /**
     *
     * @return \webignition\InternetMediaType\InternetMediaType 
     */
    public function getContentType()
    {
        return $this->contentType;
    }
}