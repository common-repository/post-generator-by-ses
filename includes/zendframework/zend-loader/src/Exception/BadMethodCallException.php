<?php
namespace Zend\Loader\Exception;
require_once __DIR__ . '/ExceptionInterface.php';
class BadMethodCallException extends \BadMethodCallException implements
    ExceptionInterface
{
}
