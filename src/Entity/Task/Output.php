<?php
namespace App\Entity\Task;

use Doctrine\ORM\Mapping as ORM;
use webignition\InternetMediaType\InternetMediaType;

/**
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="TaskOutput",
 *     indexes={
 *         @ORM\Index(name="hash_idx", columns={"hash"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\TaskOutputRepository")
 *
 */
class Output implements \JsonSerializable
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $output;

    /**
     * @var InternetMediaType
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $contentType;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    private $errorCount = 0;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    private $warningCount = 0;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true, length=32)
     */
    protected $hash;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $output
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param InternetMediaType|null $contentType
     */
    public function setContentType(?InternetMediaType $contentType)
    {
        $this->contentType = $contentType;
    }

    /**
     * @return InternetMediaType
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @param int $errorCount
     */
    public function setErrorCount($errorCount)
    {
        $this->errorCount = $errorCount;
    }

    /**
     * @return int
     */
    public function getErrorCount()
    {
        return $this->errorCount;
    }

    /**
     * @param integer $warningCount
     */
    public function setWarningCount($warningCount)
    {
        $this->warningCount = $warningCount;
    }

    /**
     * @return integer
     */
    public function getWarningCount()
    {
        return $this->warningCount;
    }

    /**
     * @param string $hash
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    public function generateHash()
    {
        $this->hash = md5('body:'.$this->getOutput().'
        content-type:'.$this->getContentType().'
        error-count:'.$this->getErrorCount().'
        warning-count:'.$this->getWarningCount());
    }

    public function jsonSerialize(): array
    {
        $contentType = null === $this->contentType
            ? null
            : (string) $this->contentType;

        return [
            'output' => $this->output,
            'content_type' => $contentType,
            'error_count' => $this->errorCount,
            'warning_count' => $this->warningCount,
        ];
    }
}
