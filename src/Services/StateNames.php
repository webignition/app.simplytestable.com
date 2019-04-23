<?php

namespace App\Services;

class StateNames extends YamlResourceLoader
{
    public function getData()
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
