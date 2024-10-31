<?php
class Google_Service_Exception extends Google_Exception
{
    protected $errors = [];
    public function __construct(
      $message,
      $code = 0,
      Exception $previous = null,
      $errors = []
  ) {
        if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
            parent::__construct($message, $code, $previous);
        } else {
            parent::__construct($message, $code);
        }
        $this->errors = $errors;
    }
    public function getErrors()
    {
        return $this->errors;
    }
}
