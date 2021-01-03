<?php

namespace Src\Objects;

class Bicycle {

    public string $name;
    public string $color;
    public string $battery;
    public string $supplier;
    public float $price;
    private int $id;

    public function __construct($record)
    {
        $this->name = $record["name"];
        $this->color = $record["color"];
        $this->battery = $record["battery"];
        $this->supplier = $record["supplier"];
        $this->price = $record["price"];
        if (isset($record["id"]))
            $this->id = $record["id"];
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

}