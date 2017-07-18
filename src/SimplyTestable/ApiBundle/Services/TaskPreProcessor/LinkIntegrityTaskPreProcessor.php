<?php
namespace SimplyTestable\ApiBundle\Services\TaskPreProcessor;

use Guzzle\Http\Exception\CurlException;
use Guzzle\Http\Exception\TooManyRedirectsException;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use webignition\HtmlDocumentLinkUrlFinder\HtmlDocumentLinkUrlFinder;
use webignition\WebResource\Exception\Exception as WebResourceException;
use SimplyTestable\ApiBundle\Entity\Task\Output;
use webignition\InternetMediaType\InternetMediaType;
use webignition\WebResource\WebResource;
use webignition\WebResource\WebPage\WebPage;

class LinkIntegrityTaskPreProcessor extends TaskPreProcessor
{
    /**
     * @param Task $task
     *
     * @return bool
     */
    public function process(Task $task)
    {
        $logger = $this->container->get('logger');
        $taskService = $this->container->get('simplytestable.services.taskservice');

        $logger->info('LinkIntegrityTaskPreProcessor::process: task [' . $task->getId() . ']');

        $rawTaskOutputs = $taskService->getEntityRepository()->findOutputByJobAndType($task);
        if (count($rawTaskOutputs) === 0) {
            return false;
        }

        $webResource = $this->getWebResource($task);
        if (is_null($webResource) || !$webResource instanceof WebPage) {
            return false;
        }

        $linkFinder = new HtmlDocumentLinkUrlFinder();
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
                $taskService->complete(
                    $task,
                    new \DateTime(),
                    $output,
                    $taskService->getCompletedState()
                );

                return true;
            }

            $parameters = ($task->hasParameters()) ? json_decode($task->getParameters(), true) : array();
            $parameters['excluded-urls'] = $this->getUniqueUrlListFromLinkIntegrityResults($linkIntegrityResults);
            $task->setParameters(json_encode($parameters));

            $task->setOutput($output);
            $taskService->getManager()->persist($task);
            $taskService->getManager()->flush();
        }

        return false;
    }

    /**
     * @param array $linkIntegrityResults
     *
     * @return string[]
     */
    private function getUniqueUrlListFromLinkIntegrityResults($linkIntegrityResults)
    {
        $urls = array();

        foreach ($linkIntegrityResults as $linkIntegrityResult) {
            if (!$this->isLinkIntegrityError($linkIntegrityResult) && !in_array($linkIntegrityResult->url, $urls)) {
                $urls[] = $linkIntegrityResult->url;
            }
        }

        return $urls;
    }

    /**
     * @param array $linkIntegrityResults
     * @return int
     */
    private function getErrorCount($linkIntegrityResults)
    {
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
     * @param string $url
     * @param array $existingLinkIntegrityResults
     *
     * @return \stdClass
     */
    private function getExistingLinkIntegrityResult($url, $existingLinkIntegrityResults)
    {
        $logger = $this->container->get('logger');

        foreach ($existingLinkIntegrityResults as $linkIntegrityResult) {
            if (is_object($linkIntegrityResult)) {
                if ($linkIntegrityResult->url == $url) {
                    return $linkIntegrityResult;
                }
            } else {
                $logger->error('LinkIntegrityTaskPreProcessor::getExistingLinkIntegrityResult: non-object found');
            }
        }
    }

    /**
     * @param array $rawTaskOutputs
     *
     * @return array
     */
    private function getLinkIntegrityResultsFromRawTaskOutputs($rawTaskOutputs)
    {
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
     * @param \stdClass $linkIntegrityResult
     *
     * @return boolean
     */
    private function isLinkIntegrityError($linkIntegrityResult)
    {
        $logger = $this->container->get('logger');

        if (!is_object($linkIntegrityResult)) {
            $logger->error('LinkIntegrityTaskPreProcessor::isLinkIntegrityError: non-object found');
            return false;
        }

        if ($linkIntegrityResult->type == 'curl') {
            return true;
        }

        $isHttpState = in_array(substr($linkIntegrityResult->state, 0, 1), array('3', '4', '5'));

        if ($linkIntegrityResult->type == 'http' && $isHttpState) {
            return true;
        }

        return false;
    }

    /**
     *
     * @param Task $task
     *
     * @return WebResource
     */
    private function getWebResource(Task $task)
    {
        $logger = $this->container->get('logger');

        try {
            $httpClientService = $this->container->get('simplytestable.services.httpclientservice');

            $httpClientService->get()->setUserAgent('ST Link integrity task pre-processor');

            $request = $httpClientService->getRequest($task->getUrl());
            $httpClientService->prepareRequest($request, $task->getParametersArray());

            $httpClientService->get()->setUserAgent(null);

            $webResourceService = $this->container->get('simplytestable.services.webresourceservice');

            return $webResourceService->get($request);
        } catch (WebResourceException $webResourceException) {
            $logger->error(sprintf(
                'LinkIntegrityTaskPreProcessor::getWebResource [%s][http exception][%s]',
                $task->getUrl(),
                $webResourceException->getResponse()->getStatusCode()
            ));
        } catch (CurlException $curlException) {
            $logger->error(sprintf(
                'LinkIntegrityTaskPreProcessor::getWebResource [%s][curl exception][%s]',
                $task->getUrl(),
                $curlException->getErrorNo()
            ));
        } catch (TooManyRedirectsException $tooManyRedirectsException) {
            $logger->error(sprintf(
                'LinkIntegrityTaskPreProcessor::getWebResource [%s][http exception][too many redirects]',
                $task->getUrl()
            ));
        }
    }
}
