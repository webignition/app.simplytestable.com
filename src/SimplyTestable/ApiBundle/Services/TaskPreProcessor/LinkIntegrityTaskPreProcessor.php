<?php
namespace SimplyTestable\ApiBundle\Services\TaskPreProcessor;

use SimplyTestable\ApiBundle\Exception\WebResourceException;
use SimplyTestable\ApiBundle\Entity\Task\Output;
use webignition\InternetMediaType\InternetMediaType;

class LinkIntegrityTaskPreProcessor extends TaskPreProcessor {
    
    const DEFAULT_MAX_AGE = 300;
    
    public function process(\SimplyTestable\ApiBundle\Entity\Task\Task $task) {                
        $task->setUrl('http://webignition.net/articles/');
        
        $rawTaskOutputs = $this->getTaskService()->getEntityRepository()->findOutputByJobAndTypeSince($task, new \DateTime('-'.$this->getMaxAge().' second'));
        if (count($rawTaskOutputs) === 0) {
            return;
        }

        $webResource = $this->getWebResource($task);
        if (is_null($webResource)) {
            return false;
        }
        
        $linkFinder = new \webignition\HtmlDocumentLinkUrlFinder\HtmlDocumentLinkUrlFinder();
        $linkFinder->setSourceUrl($task->getUrl());
        $linkFinder->setSourceContent($webResource->getContent());
        
        $links = $linkFinder->getAll();        
        $existingLinkIntegrityResults = $this->getLinkIntegrityResultsFromRawTaskOutputs($rawTaskOutputs);
        
        $linkIntegrityResults = array();
        
        foreach ($links as $link) {
            $linkIntegrityResult = $this->getExistingLinkIntegrityResult($link['url'], $existingLinkIntegrityResults);
            if (!is_null($linkIntegrityResult)) {
                $linkIntegrityResult->context = $link['element'];
                $linkIntegrityResults[] = $linkIntegrityResult;
            }            
        }
        
        if (count($linkIntegrityResults)) {
            $mediaType = new InternetMediaType();
            $mediaType->setType('application');
            $mediaType->setSubtype('json');
            
            $output = new Output();
            $output->setOutput(json_encode($linkIntegrityResults));
            $output->setContentType($mediaType);
            $output->setErrorCount($this->getErrorCount($linkIntegrityResults));
            $output->setWarningCount(0);
            
            if (count($linkIntegrityResults) == count($links)) {
                $this->getTaskService()->complete($task, new \DateTime(), $output, $this->getTaskService()->getCompletedState());
                return true;
            }
            
            $task->setOutput($output);
            $this->getTaskService()->getEntityManager()->persist($task);
            $this->getTaskService()->getEntityManager()->flush();
        }
        
        return false;        
    }
    
    
    /**
     * 
     * @return int
     */
    public function getMaxAge() {
        return is_null($this->getParameter('max_age')) ? self::DEFAULT_MAX_AGE : $this->getParameter('max_age');
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
     * @param string $url
     * @param array $existingLinkIntegrityResults
     * @return \stdClass
     */
    private function getExistingLinkIntegrityResult($url, $existingLinkIntegrityResults) {
        foreach ($existingLinkIntegrityResults as $linkIntegrityResult) {
            if ($linkIntegrityResult->url == $url) {
                return $linkIntegrityResult;
            }
        }
    }
    
    private function getLinkIntegrityResultsFromRawTaskOutputs($rawTaskOutputs) {
        $linkIntegrityResults = array();
        
        foreach ($rawTaskOutputs as $rawTaskOutput) {            
            $decodedTaskOutput = json_decode($rawTaskOutput);
            foreach ($decodedTaskOutput as $linkIntegrityResult) {
                $linkIntegrityResults[] = $linkIntegrityResult;
            }
        }        
        
        return $linkIntegrityResults;
    }
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Services\WebResourceService
     */
    private function getWebResourceService() {
        return $this->container->get('simplytestable.services.webresourceservice');
    }    
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Services\TaskService
     */
    private function getTaskService() {
        return $this->container->get('simplytestable.services.taskservice');
    }
    
    
    /**
     * 
     * @return \Symfony\Component\HttpKernel\Log\LoggerInterface
     */
    private function getLogger() {
        return $this->container->get('logger');
    }
    
    /**
     *
     * @param \SimplyTestable\ApiBundle\Entity\Task\Tas $task
     * @return WebResource 
     */
    private function getWebResource(\SimplyTestable\ApiBundle\Entity\Task\Task $task) {                        
        try {            
            $request = $this->getWebResourceService()->getHttpClientService()->getRequest($task->getUrl());
            return $this->getWebResourceService()->get($request);            
        } catch (WebResourceException $webResourceException) {
            $this->getLogger()->err('LinkIntegrityTaskPreProcessor::getWebResource ['.$task->getUrl().'][http exception]['.$webResourceException->getResponse()->getStatusCode().']');           
        } catch (\Guzzle\Http\Exception\CurlException $curlException) {            
            $this->getLogger()->err('LinkIntegrityTaskPreProcessor::getWebResource ['.$task->getUrl().'][curl exception]['.$curlException->getErrorNo().']');            
        } catch (\Guzzle\Http\Exception\TooManyRedirectsException $tooManyRedirectsException) {            
            $this->getLogger()->err('LinkIntegrityTaskPreProcessor::getWebResource ['.$task->getUrl().'][http exception][too many redirects]');
        }
    }     
    
    
}