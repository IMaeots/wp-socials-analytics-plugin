<?php

class JSONProcessorInstagram
{
    private mixed $data;


    public function __construct($json)
    {
        // Take in Json data (not file!).
        $this->data = json_decode($json, true);
    }


    public function getInstagramDataAsDict(): array
    {
        return array();
    }
}