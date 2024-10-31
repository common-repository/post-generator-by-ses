<?php
namespace Zend\Uri;
use Zend\Validator\EmailAddress as EmailValidator;
use Zend\Validator\ValidatorInterface;
class Mailto extends Uri
{
    protected static $validSchemes = ['mailto'];
    protected $emailValidator;
    public function isValid()
    {
        if ($this->host || $this->userInfo || $this->port) {
            return false;
        }
        if (empty($this->path)) {
            return false;
        }
        if (0 === strpos($this->path, '/')) {
            return false;
        }
        $validator = $this->getValidator();
        return $validator->isValid($this->path);
    }
    public function setEmail($email)
    {
        return $this->setPath($email);
    }
    public function getEmail()
    {
        return $this->getPath();
    }
    public function setValidator(ValidatorInterface $validator)
    {
        $this->emailValidator = $validator;
        return $this;
    }
    public function getValidator()
    {
        if (null === $this->emailValidator) {
            $this->setValidator(new EmailValidator());
        }
        return $this->emailValidator;
    }
}
