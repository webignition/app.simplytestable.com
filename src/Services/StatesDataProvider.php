<?php

namespace App\Services;

class StatesDataProvider extends YamlResourceDataProvider
{
    public function getData(): array
    {
        $data = parent::getData();

        $names = [];

        foreach ($data as $entity => $entityStateNames) {
            foreach ($entityStateNames as $entityStateName) {
                $names[] = $entity . '-' . $entityStateName;
            }
        }

        return $names;
    }
}
