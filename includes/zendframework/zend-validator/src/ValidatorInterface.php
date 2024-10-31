<?php
namespace Zend\Validator;
interface ValidatorInterface
{
    public function isValid($value);
    public function getMessages();
}
