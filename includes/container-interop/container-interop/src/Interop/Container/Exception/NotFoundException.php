<?php
namespace Interop\Container\Exception;
use Psr\Container\NotFoundExceptionInterface as PsrNotFoundException;
interface NotFoundException extends ContainerException, PsrNotFoundException
{
}
