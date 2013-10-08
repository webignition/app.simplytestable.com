<?php
namespace SimplyTestable\ApiBundle\Services\TaskOutputJoiner;

use SimplyTestable\ApiBundle\Entity\Task\Output as TaskOutput;

class LinkIntegrityTaskOutputJoiner extends TaskOutputJoiner {

    public function process($taskOutputs) {        
        $linkIntegrityResults = $this->getJoinedOutputBody($taskOutputs);
        
        $joinedOutput = new TaskOutput();
        
        $joinedOutput->setContentType($this->getContentType($taskOutputs));
        $joinedOutput->setErrorCount($this->getErrorCount($linkIntegrityResults));
        $joinedOutput->generateHash();
        $joinedOutput->setOutput(json_encode($linkIntegrityResults));
        $joinedOutput->setWarningCount(0);
        
        return $joinedOutput;
    }
    
    
    /**
     * 
     * @param array $taskOutputs
     * @return \webignition\InternetMediaType\InternetMediaType
     */
    private function getContentType($taskOutputs) {
        /* @var $taskOutput TaskOutput */
        $taskOutput = $taskOutputs[0];
        
        if ($taskOutput->getContentType() instanceof \webignition\InternetMediaType\InternetMediaType) {
            return $taskOutput->getContentType();            
        }        
        
        $mediaTypeParser = new \webignition\InternetMediaType\Parser\Parser();
        return $mediaTypeParser->parse($taskOutput->getContentType());
    }
    
    
    /**
     * 
     * @param array $linkIntegrityResults
     * @return int
     */
    private function getErrorCount($linkIntegrityResults) {
        $errorCount = 0;
        
        foreach ($linkIntegrityResults as $linkIntegrityResult) {
            if ($linkIntegrityResult->type == 'curl') {
                $errorCount++;
            }
            
            if ($linkIntegrityResult->type == 'http' && $linkIntegrityResult->state != 200) {
                $errorCount++;
            }
        }
        
        return $errorCount;
    }    
    
    
    
    /**
     * 
     * @param array $taskOutputs
     * @return string
     */
    private function getJoinedOutputBody($taskOutputs) {
        $linkIntegrityResults = array();
        
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
     * 
     * @param array $linkIntegrityResults
     * @param \stdClass $linkIntegrityResult
     * @return boolean
     */
    private function contains($linkIntegrityResults, $linkIntegrityResult) {
        foreach ($linkIntegrityResults as $comparator) {
            if ($this->areLinkIntegrityResultsEqual($comparator, $linkIntegrityResult)) {
                return true;
            }
        }
        
        return false;
    }
    
    
    /**
     * 
     * @param \stdClass $a
     * @param \stdClass $b
     * @return boolean
     */
    private function areLinkIntegrityResultsEqual($a, $b) {
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