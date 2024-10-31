<?php
namespace Zend\Validator\Translator;
interface TranslatorInterface
{
    public function translate($message, $textDomain = 'default', $locale = null);
}
