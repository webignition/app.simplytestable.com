<?php
namespace SimplyTestable\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="TimePeriod",
 *     indexes={
 *         @ORM\Index(name="start_idx", columns={"startDateTime"}),
 *         @ORM\Index(name="end_idx", columns={"endDateTime"}),
 *     }
 * )
 */
class TimePeriod implements \JsonSerializable
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
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $startDateTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $endDateTime;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \DateTime $startDateTime
     */
    public function setStartDateTime($startDateTime)
    {
        $this->startDateTime = $startDateTime;
    }

    /**
     * @return \DateTime
     */
    public function getStartDateTime()
    {
        return $this->startDateTime;
    }

    /**
     * @param \DateTime $endDateTime
     */
    public function setEndDateTime($endDateTime)
    {
        $this->endDateTime = $endDateTime;
    }

    /**
     * @return \DateTime
     */
    public function getEndDateTime()
    {
        return $this->endDateTime;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->startDateTime) && empty($this->endDateTime);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $timePeriodData = [];

        if ($this->isEmpty()) {
            return $timePeriodData;
        }

        if (!empty($this->startDateTime)) {
            $timePeriodData['start_date_time'] = $this->startDateTime->format('c');
        }

        if (!empty($this->endDateTime)) {
            $timePeriodData['end_date_time'] = $this->endDateTime->format('c');
        }

        return $timePeriodData;
    }
}
