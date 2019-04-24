<?php

namespace App\Services;

class UserDataProvider implements DataProviderInterface
{
    private $data;

    public function __construct(array $userData)
    {
        $this->data = $userData;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
