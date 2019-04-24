<?php

namespace App\Services;

use Symfony\Component\Yaml\Yaml;

class YamlResourceLoader
{
    /**
     * @var string
     */
    private $resourcePath;

    /**
     * @var array
     */
    private $data = [];

    public function __construct(string $resourcePath)
    {
        $this->resourcePath = $resourcePath;
    }

    public function getData()
    {
        if (empty($this->data)) {
            $this->loadData();
        }

        return $this->data;
    }

    private function loadData()
    {
        $this->data = Yaml::parseFile($this->resourcePath);
    }
}
