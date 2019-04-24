<?php

namespace App\Services;

class YamlResourceDataProvider extends YamlResourceLoader implements DataProviderInterface
{
    public function getData(): array
    {
        return parent::getData();
    }
}
