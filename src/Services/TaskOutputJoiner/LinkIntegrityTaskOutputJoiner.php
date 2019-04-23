<?php
namespace App\Services\TaskOutputJoiner;

use App\Entity\Task\Output as TaskOutput;
use App\Entity\Task\Type as TaskType;
use App\Services\TaskTypeService;
use webignition\InternetMediaType\InternetMediaType;
use webignition\InternetMediaType\Parser\Parser as InternetMediaTypeParser;

class LinkIntegrityTaskOutputJoiner implements TaskOutputJoinerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handles(TaskType $taskType)
    {
        return $taskType->getName() === TaskTypeService::LINK_INTEGRITY_TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function join($taskOutputs)
    {
        $filteredTaskOutputs = [];
        foreach ($taskOutputs as $taskOutput) {
            if ($taskOutput instanceof TaskOutput) {
                $filteredTaskOutputs[] = $taskOutput;
            }
        }

        if (count($filteredTaskOutputs) === 1) {
            return $filteredTaskOutputs[0];
        }

        $linkIntegrityResults = $this->getJoinedOutputBody($taskOutputs);

        $joinedOutput = new TaskOutput();

        $contentType = new InternetMediaType();
        $contentType->setType('application');
        $contentType->setSubtype('json');

        $joinedOutput->setContentType($contentType);
        $joinedOutput->setErrorCount($this->getErrorCount($linkIntegrityResults));
        $joinedOutput->generateHash();
        $joinedOutput->setOutput(json_encode($linkIntegrityResults));
        $joinedOutput->setWarningCount(0);

        return $joinedOutput;
    }

    /**
     * @param array $linkIntegrityResults
     *
     * @return int
     */
    private function getErrorCount($linkIntegrityResults)
    {
        $errorCount = 0;

        foreach ($linkIntegrityResults as $linkIntegrityResult) {
            if ($this->isLinkIntegrityError($linkIntegrityResult)) {
                $errorCount++;
            }
        }

        return $errorCount;
    }

    /**
     * @param \stdClass $linkIntegrityResult
     *
     * @return bool
     */
    private function isLinkIntegrityError($linkIntegrityResult)
    {
        if ($linkIntegrityResult->type == 'curl') {
            return true;
        }

        if (in_array(substr($linkIntegrityResult->state, 0, 1), array('3', '4', '5'))) {
            return true;
        }

        return false;
    }

    /**
     * @param TaskOutput[] $taskOutputs
     *
     * @return array
     */
    private function getJoinedOutputBody($taskOutputs)
    {
        $linkIntegrityResults = [];

        foreach ($taskOutputs as $taskOutput) {
            /* @var $taskOutput TaskOutput */
            $decodedTaskOutput = json_decode($taskOutput->getOutput());

            foreach ($decodedTaskOutput as $linkIntegrityResult) {
                if (!$this->contains($linkIntegrityResults, $linkIntegrityResult)) {
                    $linkIntegrityResults[] = $linkIntegrityResult;
                }
            }
        }

        return $linkIntegrityResults;
    }

    /**
     * @param array $linkIntegrityResults
     * @param \stdClass $linkIntegrityResult
     *
     * @return bool
     */
    private function contains($linkIntegrityResults, $linkIntegrityResult)
    {
        foreach ($linkIntegrityResults as $comparator) {
            if ($this->areLinkIntegrityResultsEqual($comparator, $linkIntegrityResult)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \stdClass $a
     * @param \stdClass $b
     *
     * @return bool
     */
    private function areLinkIntegrityResultsEqual($a, $b)
    {
        $properties = array('context', 'state', 'type', 'url');

        foreach ($properties as $property) {
            if (!isset($a->$property)) {
                return false;
            }

            if (!isset($b->$property)) {
                return false;
            }

            if ($a->$property != $b->$property) {
                return false;
            }
        }

        return true;
    }
}
