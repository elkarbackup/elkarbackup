<?php
namespace App\Exception;

use \Exception;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class APIException extends Exception implements ExceptionInterface
{
}

