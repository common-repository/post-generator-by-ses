<?php
namespace Zend\Validator\Translator;
interface TranslatorAwareInterface
{
    public function setTranslator(TranslatorInterface $translator = null, $textDomain = null);
    public function getTranslator();
    public function hasTranslator();
    public function setTranslatorEnabled($enabled = true);
    public function isTranslatorEnabled();
    public function setTranslatorTextDomain($textDomain = 'default');
    public function getTranslatorTextDomain();
}
