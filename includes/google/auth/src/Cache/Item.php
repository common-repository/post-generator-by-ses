<?php
namespace Google\Auth\Cache;
use Psr\Cache\CacheItemInterface;
final class Item implements CacheItemInterface
{
    private $key;
    private $value;
    private $expiration;
    private $isHit = false;
    public function __construct($key)
    {
        $this->key = $key;
    }
    public function getKey()
    {
        return $this->key;
    }
    public function get()
    {
        return $this->isHit() ? $this->value : null;
    }
    public function isHit()
    {
        if (!$this->isHit) {
            return false;
        }
        if ($this->expiration === null) {
            return true;
        }
        return new \DateTime() < $this->expiration;
    }
    public function set($value)
    {
        $this->isHit = true;
        $this->value = $value;
        return $this;
    }
    public function expiresAt($expiration)
    {
        if ($this->isValidExpiration($expiration)) {
            $this->expiration = $expiration;
            return $this;
        }
        $implementationMessage = interface_exists('DateTimeInterface')
            ? 'implement interface DateTimeInterface'
            : 'be an instance of DateTime';
        $error = sprintf(
            'Argument 1 passed to %s::expiresAt() must %s, %s given',
            get_class($this),
            $implementationMessage,
            gettype($expiration)
        );
        $this->handleError($error);
    }
    public function expiresAfter($time)
    {
        if (is_int($time)) {
            $this->expiration = new \DateTime("now + $time seconds");
        } elseif ($time instanceof \DateInterval) {
            $this->expiration = (new \DateTime())->add($time);
        } elseif ($time === null) {
            $this->expiration = $time;
        } else {
            $message = 'Argument 1 passed to %s::expiresAfter() must be an ' .
                       'instance of DateInterval or of the type integer, %s given';
            $error = sprintf($message, get_class($this), gettype($time));
            $this->handleError($error);
        }
        return $this;
    }
    private function handleError($error)
    {
        if (class_exists('TypeError')) {
            throw new \TypeError($error);
        }
        trigger_error($error, E_USER_ERROR);
    }
    private function isValidExpiration($expiration)
    {
        if ($expiration === null) {
            return true;
        }
        if ($expiration instanceof \DateTimeInterface) {
            return true;
        }
        if ($expiration instanceof \DateTime) {
            return true;
        }
        return false;
    }
}
