<?php
namespace SimplyTestable\ApiBundle\Services\TaskPreProcessor;

use webignition\WebResource\Exception\Exception as WebResourceException;
use SimplyTestable\ApiBundle\Entity\Task\Output;
use webignition\InternetMediaType\InternetMediaType;
use webignition\WebResource\WebResource;
use webignition\WebResource\WebPage\WebPage;

class LinkIntegrityTaskPreProcessor extends TaskPreProcessor {
    
    public function process(\SimplyTestable\ApiBundle\Entity\Task\Task $task) {
        $this->container->get('logger')->info('LinkIntegrityTaskPreProcessor::process: task [' . $task->getId() . ']');

        $rawTaskOutputs = $this->getTaskService()->getEntityRepository()->findOutputByJobAndType($task);
        if (count($rawTaskOutputs) === 0) {
            return false;
        }

        $webResource = $this->getWebResource($task);
        if (is_null($webResource) || !$webResource instanceof WebPage) {
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
            
            $parameters = ($task->hasParameters()) ? json_decode($task->getParameters(), true) : array();
            $parameters['excluded-urls'] = $this->getUniqueUrlListFromLinkIntegrityResults($linkIntegrityResults);            
            $task->setParameters(json_encode($parameters));
            
            $task->setOutput($output);
            $this->getTaskService()->getManager()->persist($task);
            $this->getTaskService()->getManager()->flush();
        }
        
        return false;        
    }
    
    private function getUniqueUrlListFromLinkIntegrityResults($linkIntegrityResults) {
        $urls = array();
        
        foreach ($linkIntegrityResults as $linkIntegrityResult) {
            if (!$this->isLinkIntegrityError($linkIntegrityResult) && !in_array($linkIntegrityResult->url, $urls)) {
                $urls[] = $linkIntegrityResult->url;
            }
        }
        
        return $urls;
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
            if (is_object($linkIntegrityResult)) {
                if ($linkIntegrityResult->url == $url) {
                    return $linkIntegrityResult;
                }
            } else {
                $this->container->get('logger')->error('LinkIntegrityTaskPreProcessor::getExistingLinkIntegrityResult: non-object found');
            }
        }
    }
    
    private function getLinkIntegrityResultsFromRawTaskOutputs($rawTaskOutputs) {        
        $linkIntegrityResults = array();
        
        foreach ($rawTaskOutputs as $rawTaskOutput) {
            $decodedTaskOutput = json_decode($rawTaskOutput);

            if (!is_array($decodedTaskOutput)) {
                continue;
            }

            foreach ($decodedTaskOutput as $linkIntegrityResult) {
                if (!$this->isLinkIntegrityError($linkIntegrityResult)) {
                    $linkIntegrityResults[] = $linkIntegrityResult;
                }
            }
        }        
        
        return $linkIntegrityResults;
    }
    
    /**
     * 
     * @param \stdClass $linkIntegrityResult
     * @return boolean
     */
    private function isLinkIntegrityError($linkIntegrityResult) {
        if (!is_object($linkIntegrityResult)) {
            $this->container->get('logger')->error('LinkIntegrityTaskPreProcessor::isLinkIntegrityError: non-object found');
            return false;
        }

        if ($linkIntegrityResult->type == 'curl') {
            return true;
        }
        
        if ($linkIntegrityResult->type == 'http' && in_array(substr($linkIntegrityResult->state, 0, 1), array('3', '4', '5'))) {
            return true;
        }
        
        return false;
    }    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Services\HttpClientService
     */
    private function getHttpClientService() {
        return $this->container->get('simplytestable.services.httpclientservice');
    }     
    
    /**
     * 
     * @return \webignition\WebResource\Service\Service
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
     * @param \SimplyTestable\ApiBundle\Entity\Task\Task $task
     * @return WebResource 
     */
    private function getWebResource(\SimplyTestable\ApiBundle\Entity\Task\Task $task) {        
        try {
            $this->getHttpClientService()->get()->setUserAgent('ST Link integrity task pre-processor');

            $request = $this->getHttpClientService()->getRequest($task->getUrl());
            $this->getHttpClientService()->prepareRequest($request, $task->getParametersArray());

            $this->getHttpClientService()->get()->setUserAgent(null);
            
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