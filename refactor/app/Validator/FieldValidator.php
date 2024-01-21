<?php
namespace Validator;

use ValidationException;

class FieldValidator
{
    public static function validateField($data, $fieldName, $message)
    {
        if (!isset($data[$fieldName]) || empty($data[$fieldName])) {
            throw new ValidationException($fieldName, $message);
        }
    }
}
