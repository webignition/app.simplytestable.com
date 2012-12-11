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
 * @SerializerAnnotation\ExclusionPolicy("all")
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
     * @SerializerAnnotation\Expose
     */
    protected $output;
    
    
    /**
     *
     * @var \webignition\InternetMediaType\InternetMediaType 
     * @ORM\Column(type="string", nullable=true)
     * @SerializerAnnotation\Expose
     */
    protected $contentType;
    
    /**
     *
     * @var int 
     * @ORM\Column(type="integer", nullable=false)
     * @SerializerAnnotation\Expose
     */
    private $errorCount;   
    
    
    /**
     *
     * @var int 
     * @ORM\Column(type="integer", nullable=false, options={"default" = 0})
     */    
    private $warningCount = 0;    


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
    
    
    /**
     * Set output
     *
     * @param int $errorCount
     * @return Output
     */
    public function setErrorCount($errorCount)
    {
        $this->errorCount = $errorCount;
    
        return $this;
    }

    /**
     * Get error count
     *
     * @return int 
     */
    public function getErrorCount()
    {
        return $this->errorCount;
    }
    
    /**
     * Set warningCount
     *
     * @param integer $warningCount
     * @return Output
     */
    public function setWarningCount($warningCount)
    {
        $this->warningCount = $warningCount;
    
        return $this;
    }

    /**
     * Get warningCount
     *
     * @return integer 
     */
    public function getWarningCount()
    {
        return $this->warningCount;
    }    
}