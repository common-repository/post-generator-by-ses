<?php
namespace Zend\Crypt\Exception;
use Interop\Container\Exception\NotFoundException as InteropNotFoundException;
class NotFoundException extends \DomainException implements InteropNotFoundException
{
}
