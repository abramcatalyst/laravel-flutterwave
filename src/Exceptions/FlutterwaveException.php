<?php

namespace AbramCatalyst\Flutterwave\Exceptions;

use Exception;

class FlutterwaveException extends Exception
{
    /**
     * Create a new Flutterwave exception instance.
     *
     * @param  string  $message
     * @param  int  $code
     * @param  \Exception|null  $previous
     * @return void
     */
    public function __construct($message = '', $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

