<?php

use Src\Objects\Bicycle;

namespace Src\Objects;

class Supplier {

    private string $name;
    private string $address;
    private string $description;
    private $bicycles;

    public function __construct($record)
    {
        $this->name = $record["supplier"];
        $this->address = $record["address"];
        $this->description = $record["description"];
        $this->bicycles = array();
    }

    public function __get($property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }

    public function __set($property, $value) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }
    }
    
    public function add_bicycle($bicycle){
        array_push($this->bicycles, $bicycle);
    }

}