<?php
namespace Zend\Validator;
use Traversable;
use Zend\Stdlib\ArrayUtils;
abstract class AbstractValidator implements
    Translator\TranslatorAwareInterface,
    ValidatorInterface
{
    protected $value;
    protected static $defaultTranslator;
    protected static $defaultTranslatorTextDomain = 'default';
    protected static $messageLength = -1;
    protected $abstractOptions = [
        'messages'             => [],         'messageTemplates'     => [],         'messageVariables'     => [],         'translator'           => null,            'translatorTextDomain' => null,            'translatorEnabled'    => true,            'valueObscured'        => false,                                                  ];
    public function __construct($options = null)
    {
                if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }
        if (isset($this->messageTemplates)) {
            $this->abstractOptions['messageTemplates'] = $this->messageTemplates;
        }
        if (isset($this->messageVariables)) {
            $this->abstractOptions['messageVariables'] = $this->messageVariables;
        }
        if (is_array($options)) {
            $this->setOptions($options);
        }
    }
    public function getOption($option)
    {
        if (array_key_exists($option, $this->abstractOptions)) {
            return $this->abstractOptions[$option];
        }
        if (isset($this->options) && array_key_exists($option, $this->options)) {
            return $this->options[$option];
        }
        throw new Exception\InvalidArgumentException("Invalid option '$option'");
    }
    public function getOptions()
    {
        $result = $this->abstractOptions;
        if (isset($this->options)) {
            $result += $this->options;
        }
        return $result;
    }
    public function setOptions($options = [])
    {
        if (! is_array($options) && ! $options instanceof Traversable) {
            throw new Exception\InvalidArgumentException(__METHOD__ . ' expects an array or Traversable');
        }
        foreach ($options as $name => $option) {
            $fname = 'set' . ucfirst($name);
            $fname2 = 'is' . ucfirst($name);
            if (($name != 'setOptions') && method_exists($this, $name)) {
                $this->{$name}($option);
            } elseif (($fname != 'setOptions') && method_exists($this, $fname)) {
                $this->{$fname}($option);
            } elseif (method_exists($this, $fname2)) {
                $this->{$fname2}($option);
            } elseif (isset($this->options)) {
                $this->options[$name] = $option;
            } else {
                $this->abstractOptions[$name] = $option;
            }
        }
        return $this;
    }
    public function getMessages()
    {
        return array_unique($this->abstractOptions['messages'], SORT_REGULAR);
    }
    public function __invoke($value)
    {
        return $this->isValid($value);
    }
    public function getMessageVariables()
    {
        return array_keys($this->abstractOptions['messageVariables']);
    }
    public function getMessageTemplates()
    {
        return $this->abstractOptions['messageTemplates'];
    }
    public function setMessage($messageString, $messageKey = null)
    {
        if ($messageKey === null) {
            $keys = array_keys($this->abstractOptions['messageTemplates']);
            foreach ($keys as $key) {
                $this->setMessage($messageString, $key);
            }
            return $this;
        }
        if (! isset($this->abstractOptions['messageTemplates'][$messageKey])) {
            throw new Exception\InvalidArgumentException("No message template exists for key '$messageKey'");
        }
        $this->abstractOptions['messageTemplates'][$messageKey] = $messageString;
        return $this;
    }
    public function setMessages(array $messages)
    {
        foreach ($messages as $key => $message) {
            $this->setMessage($message, $key);
        }
        return $this;
    }
    public function __get($property)
    {
        if ($property == 'value') {
            return $this->value;
        }
        if (array_key_exists($property, $this->abstractOptions['messageVariables'])) {
            $result = $this->abstractOptions['messageVariables'][$property];
            if (is_array($result)) {
                return $this->{key($result)}[current($result)];
            }
            return $this->{$result};
        }
        if (isset($this->messageVariables) && array_key_exists($property, $this->messageVariables)) {
            $result = $this->{$this->messageVariables[$property]};
            if (is_array($result)) {
                return $this->{key($result)}[current($result)];
            }
            return $this->{$result};
        }
        throw new Exception\InvalidArgumentException("No property exists by the name '$property'");
    }
    protected function createMessage($messageKey, $value)
    {
        if (! isset($this->abstractOptions['messageTemplates'][$messageKey])) {
            return;
        }
        $message = $this->abstractOptions['messageTemplates'][$messageKey];
        $message = $this->translateMessage($messageKey, $message);
        if (is_object($value) &&
            ! in_array('__toString', get_class_methods($value))
        ) {
            $value = get_class($value) . ' object';
        } elseif (is_array($value)) {
            $value = var_export($value, 1);
        } else {
            $value = (string) $value;
        }
        if ($this->isValueObscured()) {
            $value = str_repeat('*', strlen($value));
        }
        $message = str_replace('%value%', (string) $value, $message);
        foreach ($this->abstractOptions['messageVariables'] as $ident => $property) {
            if (is_array($property)) {
                $value = $this->{key($property)}[current($property)];
                if (is_array($value)) {
                    $value = '[' . implode(', ', $value) . ']';
                }
            } else {
                $value = $this->$property;
            }
            $message = str_replace("%$ident%", (string) $value, $message);
        }
        $length = self::getMessageLength();
        if (($length > -1) && (strlen($message) > $length)) {
            $message = substr($message, 0, ($length - 3)) . '...';
        }
        return $message;
    }
    protected function error($messageKey, $value = null)
    {
        if ($messageKey === null) {
            $keys = array_keys($this->abstractOptions['messageTemplates']);
            $messageKey = current($keys);
        }
        if ($value === null) {
            $value = $this->value;
        }
        $this->abstractOptions['messages'][$messageKey] = $this->createMessage($messageKey, $value);
    }
    protected function getValue()
    {
        return $this->value;
    }
    protected function setValue($value)
    {
        $this->value               = $value;
        $this->abstractOptions['messages'] = [];
    }
    public function setValueObscured($flag)
    {
        $this->abstractOptions['valueObscured'] = (bool) $flag;
        return $this;
    }
    public function isValueObscured()
    {
        return $this->abstractOptions['valueObscured'];
    }
    public function setTranslator(Translator\TranslatorInterface $translator = null, $textDomain = null)
    {
        $this->abstractOptions['translator'] = $translator;
        if (null !== $textDomain) {
            $this->setTranslatorTextDomain($textDomain);
        }
        return $this;
    }
    public function getTranslator()
    {
        if (! $this->isTranslatorEnabled()) {
            return;
        }
        if (null === $this->abstractOptions['translator']) {
            $this->abstractOptions['translator'] = self::getDefaultTranslator();
        }
        return $this->abstractOptions['translator'];
    }
    public function hasTranslator()
    {
        return (bool) $this->abstractOptions['translator'];
    }
    public function setTranslatorTextDomain($textDomain = 'default')
    {
        $this->abstractOptions['translatorTextDomain'] = $textDomain;
        return $this;
    }
    public function getTranslatorTextDomain()
    {
        if (null === $this->abstractOptions['translatorTextDomain']) {
            $this->abstractOptions['translatorTextDomain'] =
                self::getDefaultTranslatorTextDomain();
        }
        return $this->abstractOptions['translatorTextDomain'];
    }
    public static function setDefaultTranslator(Translator\TranslatorInterface $translator = null, $textDomain = null)
    {
        static::$defaultTranslator = $translator;
        if (null !== $textDomain) {
            self::setDefaultTranslatorTextDomain($textDomain);
        }
    }
    public static function getDefaultTranslator()
    {
        return static::$defaultTranslator;
    }
    public static function hasDefaultTranslator()
    {
        return (bool) static::$defaultTranslator;
    }
    public static function setDefaultTranslatorTextDomain($textDomain = 'default')
    {
        static::$defaultTranslatorTextDomain = $textDomain;
    }
    public static function getDefaultTranslatorTextDomain()
    {
        return static::$defaultTranslatorTextDomain;
    }
    public function setTranslatorEnabled($flag = true)
    {
        $this->abstractOptions['translatorEnabled'] = (bool) $flag;
        return $this;
    }
    public function isTranslatorEnabled()
    {
        return $this->abstractOptions['translatorEnabled'];
    }
    public static function getMessageLength()
    {
        return static::$messageLength;
    }
    public static function setMessageLength($length = -1)
    {
        static::$messageLength = $length;
    }
    protected function translateMessage($messageKey, $message)
    {
        $translator = $this->getTranslator();
        if (! $translator) {
            return $message;
        }
        return $translator->translate($message, $this->getTranslatorTextDomain());
    }
}
