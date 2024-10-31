<?php
class Twig_Util_TemplateDirIterator extends IteratorIterator
{
    public function current()
    {
        return file_get_contents(parent::current());
    }
    public function key()
    {
        return (string) parent::key();
    }
}
class_alias('Twig_Util_TemplateDirIterator', 'Twig\Util\TemplateDirIterator', false);
