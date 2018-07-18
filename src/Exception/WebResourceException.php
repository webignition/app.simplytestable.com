<?php

namespace App\Exception;
use \Exception as BaseException;

class WebResourceException extends BaseException {

    /**
     *
     * @var \Guzzle\Http\Message\Request
     */
    private $response;


    /**
     *
     * @var \Guzzle\Http\Message\Response
     */
    private $request;


    /**
     *
     * @param \Guzzle\Http\Message\Response $response
     * @param \Guzzle\Http\Message\Request $request
     */
    public function __construct(\Guzzle\Http\Message\Response $response, \Guzzle\Http\Message\Request $request = null) {
        $this->response = $response;
        $this->request = $request;

        parent::__construct($response->getReasonPhrase(), $response->getStatusCode());
    }


    /**
     *
     * @return \Guzzle\Http\Message\Response
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     *
     * @return \Guzzle\Http\Message\Request
     */
    public function getRequest() {
        return $this->request;
    }

}