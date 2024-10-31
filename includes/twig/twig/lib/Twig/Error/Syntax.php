<?php
class Twig_Error_Syntax extends Twig_Error
{
    public function addSuggestions($name, array $items)
    {
        if (!$alternatives = self::computeAlternatives($name, $items)) {
            return;
        }
        $this->appendMessage(sprintf(' Did you mean "%s"?', implode('", "', $alternatives)));
    }
    public static function computeAlternatives($name, $items)
    {
        $alternatives = [];
        foreach ($items as $item) {
            $lev = levenshtein($name, $item);
            if ($lev <= strlen($name) / 3 || false !== strpos($item, $name)) {
                $alternatives[$item] = $lev;
            }
        }
        asort($alternatives);
        return array_keys($alternatives);
    }
}
class_alias('Twig_Error_Syntax', 'Twig\Error\SyntaxError', false);
