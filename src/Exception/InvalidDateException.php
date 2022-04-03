<?php
declare(strict_types=1);

namespace App\Exception;

final class InvalidDateException extends \Exception
{
    protected $message = 'The given date is invalid';
}
