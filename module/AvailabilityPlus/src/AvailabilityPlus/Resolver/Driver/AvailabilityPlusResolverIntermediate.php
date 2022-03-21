<?php

namespace AvailabilityPlus\Resolver\Driver;

class AvailabilityPlusResolverIntermediate extends AvailabilityPlusResolver
{
    private function setObjectPathValue($key, $path, $value) {
        switch(count($path)) {
            case 1 :
                $this->parsed_data->item[$key][$path[0]] = $value;
                break;
            case 2 :
                $this->parsed_data->item[$key][$path[0]][$path[1]] = $value;
                break;
            case 3 :
                $this->parsed_data->item[$key][$path[0]][$path[1]][$path[2]] = $value;
                break;
            case 4 :
                $this->parsed_data->item[$key][$path[0]][$path[1]][$path[2]][$path[3]] = $value;
                break;
            case 5 :
                $this->parsed_data->item[$key][$path[0]][$path[1]][$path[2]][$path[3]][$path[4]] = $value;
                break;
        }
    }
}

