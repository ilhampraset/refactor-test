<?php

class ValidationException extends Exception{
    public $field;
    public $message;

    public function __construct($field, $message)
    {
        $this->field = $field;
        $this->message = $message;

        parent::__construct($message);
    }
}