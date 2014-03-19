<?php
namespace SimplyTestable\ApiBundle\Services\TaskPreProcessor;

use SimplyTestable\ApiBundle\Exception\WebResourceException;
use SimplyTestable\ApiBundle\Entity\Task\Output;
use webignition\InternetMediaType\InternetMediaType;

class LinkIntegrityTaskPreProcessor extends TaskPreProcessor {
    
    public function process(\SimplyTestable\ApiBundle\Entity\Task\Task $task) {        
        $rawTaskOutputs = $this->getTaskService()->getEntityRepository()->findOutputByJobAndType($task);
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
            
            $parameters = ($task->hasParameters()) ? json_decode($task->getParameters(), true) : array();
            $parameters['excluded-urls'] = $this->getUniqueUrlListFromLinkIntegrityResults($linkIntegrityResults);            
            $task->setParameters(json_encode($parameters));
            
            $task->setOutput($output);
            $this->getTaskService()->getEntityManager()->persist($task);
            $this->getTaskService()->getEntityManager()->flush();
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
            
            $this->setRequestAuthentication($request, $task);
            $this->setRequestCookies($request, $task);            
            
            return $this->getWebResourceService()->get($request);            
        } catch (WebResourceException $webResourceException) {
            $this->getLogger()->err('LinkIntegrityTaskPreProcessor::getWebResource ['.$task->getUrl().'][http exception]['.$webResourceException->getResponse()->getStatusCode().']');           
        } catch (\Guzzle\Http\Exception\CurlException $curlException) {            
            $this->getLogger()->err('LinkIntegrityTaskPreProcessor::getWebResource ['.$task->getUrl().'][curl exception]['.$curlException->getErrorNo().']');            
        } catch (\Guzzle\Http\Exception\TooManyRedirectsException $tooManyRedirectsException) {            
            $this->getLogger()->err('LinkIntegrityTaskPreProcessor::getWebResource ['.$task->getUrl().'][http exception][too many redirects]');
        }
    }    
    
    
    /**
     * 
     * @param \Guzzle\Http\Message\Request $request
     * @param \SimplyTestable\ApiBundle\Entity\Task\Task $task
     */
    private function setRequestAuthentication(\Guzzle\Http\Message\Request $request, \SimplyTestable\ApiBundle\Entity\Task\Task $task) {
        if ($task->hasParameter('http-auth-username') || $task->hasParameter('http-auth-password')) {            
            $request->setAuth(
                ($task->hasParameter('http-auth-username')) ? $task->getParameter('http-auth-username') : '',
                ($task->hasParameter('http-auth-password')) ? $task->getParameter('http-auth-password') : '',
                'any'
            );
        }
    }      
    
    
    
    /**
     * 
     * @param \Guzzle\Http\Message\Request $request
     * @param \SimplyTestable\ApiBundle\Entity\Task\Task $task
     */
    private function setRequestCookies(\Guzzle\Http\Message\Request $request, \SimplyTestable\ApiBundle\Entity\Task\Task $task) {
        if (!is_null($request->getCookies())) {
            foreach ($request->getCookies() as $name => $value) {
                $request->removeCookie($name);
            }
        }
        
        if ($task->hasParameter('cookies')) {            
            $cookieUrlMatcher = new \webignition\Cookie\UrlMatcher\UrlMatcher();
            
            foreach ($task->getParameter('cookies') as $cookie) {                
                if ($cookieUrlMatcher->isMatch($cookie, $request->getUrl())) {
                    $request->addCookie($cookie['name'], $cookie['value']);
                }
            }             
        }

    }     
    
    
}