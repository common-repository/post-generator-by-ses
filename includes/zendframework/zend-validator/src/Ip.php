<?php
namespace Zend\Validator;
use Traversable;
class Ip extends AbstractValidator
{
    const INVALID        = 'ipInvalid';
    const NOT_IP_ADDRESS = 'notIpAddress';
    protected $messageTemplates = [
        self::INVALID        => 'Invalid type given. String expected',
        self::NOT_IP_ADDRESS => "The input does not appear to be a valid IP address",
    ];
    protected $options = [
        'allowipv4'      => true,         'allowipv6'      => true,         'allowipvfuture' => false,         'allowliteral'   => true,     ];
    public function setOptions($options = [])
    {
        parent::setOptions($options);
        if (! $this->options['allowipv4'] && ! $this->options['allowipv6'] && ! $this->options['allowipvfuture']) {
            throw new Exception\InvalidArgumentException('Nothing to validate. Check your options');
        }
        return $this;
    }
    public function isValid($value)
    {
        if (! is_string($value)) {
            $this->error(self::INVALID);
            return false;
        }
        $this->setValue($value);
        if ($this->options['allowipv4'] && $this->validateIPv4($value)) {
            return true;
        } else {
            if ((bool) $this->options['allowliteral']) {
                static $regex = '/^\[(.*)\]$/';
                if ((bool) preg_match($regex, $value, $matches)) {
                    $value = $matches[1];
                }
            }
            if (($this->options['allowipv6'] && $this->validateIPv6($value)) ||
                ($this->options['allowipvfuture'] && $this->validateIPvFuture($value))
            ) {
                return true;
            }
        }
        $this->error(self::NOT_IP_ADDRESS);
        return false;
    }
    protected function validateIPv4($value)
    {
        if (preg_match('/^([01]{8}\.){3}[01]{8}\z/i', $value)) {
                        $value = bindec(substr($value, 0, 8)) . '.' . bindec(substr($value, 9, 8)) . '.'
                   . bindec(substr($value, 18, 8)) . '.' . bindec(substr($value, 27, 8));
        } elseif (preg_match('/^([0-9]{3}\.){3}[0-9]{3}\z/i', $value)) {
                        $value = (int) substr($value, 0, 3) . '.' . (int) substr($value, 4, 3) . '.'
                   . (int) substr($value, 8, 3) . '.' . (int) substr($value, 12, 3);
        } elseif (preg_match('/^([0-9a-f]{2}\.){3}[0-9a-f]{2}\z/i', $value)) {
                        $value = hexdec(substr($value, 0, 2)) . '.' . hexdec(substr($value, 3, 2)) . '.'
                   . hexdec(substr($value, 6, 2)) . '.' . hexdec(substr($value, 9, 2));
        }
        $ip2long = ip2long($value);
        if ($ip2long === false) {
            return false;
        }
        return ($value == long2ip($ip2long));
    }
    protected function validateIPv6($value)
    {
        if (strlen($value) < 3) {
            return $value == '::';
        }
        if (strpos($value, '.')) {
            $lastcolon = strrpos($value, ':');
            if (! ($lastcolon && $this->validateIPv4(substr($value, $lastcolon + 1)))) {
                return false;
            }
            $value = substr($value, 0, $lastcolon) . ':0:0';
        }
        if (strpos($value, '::') === false) {
            return preg_match('/\A(?:[a-f0-9]{1,4}:){7}[a-f0-9]{1,4}\z/i', $value);
        }
        $colonCount = substr_count($value, ':');
        if ($colonCount < 8) {
            return preg_match('/\A(?::|(?:[a-f0-9]{1,4}:)+):(?:(?:[a-f0-9]{1,4}:)*[a-f0-9]{1,4})?\z/i', $value);
        }
                if ($colonCount == 8) {
            return preg_match('/\A(?:::)?(?:[a-f0-9]{1,4}:){6}[a-f0-9]{1,4}(?:::)?\z/i', $value);
        }
        return false;
    }
    protected function validateIPvFuture($value)
    {
        static $regex = '/^v([[:xdigit:]]+)\.[[:alnum:]\-\._~!\$&\'\(\)\*\+,;=:]+$/';
        $result = (bool) preg_match($regex, $value, $matches);
        return ($result && $matches[1] != 4 && $matches[1] != 6);
    }
}
