<?php
class Twig_Profiler_Dumper_Html extends Twig_Profiler_Dumper_Base
{
    private static $colors = [
        'block' => '#dfd',
        'macro' => '#ddf',
        'template' => '#ffd',
        'big' => '#d44',
    ];
    public function dump(Twig_Profiler_Profile $profile)
    {
        return '<pre>' . parent::dump($profile) . '</pre>';
    }
    protected function formatTemplate(Twig_Profiler_Profile $profile, $prefix)
    {
        return sprintf('%s└ <span style="background-color: %s">%s</span>', $prefix, self::$colors['template'], $profile->getTemplate());
    }
    protected function formatNonTemplate(Twig_Profiler_Profile $profile, $prefix)
    {
        return sprintf('%s└ %s::%s(<span style="background-color: %s">%s</span>)', $prefix, $profile->getTemplate(), $profile->getType(), isset(self::$colors[$profile->getType()]) ? self::$colors[$profile->getType()] : 'auto', $profile->getName());
    }
    protected function formatTime(Twig_Profiler_Profile $profile, $percent)
    {
        return sprintf('<span style="color: %s">%.2fms/%.0f%%</span>', $percent > 20 ? self::$colors['big'] : 'auto', $profile->getDuration() * 1000, $percent);
    }
}
class_alias('Twig_Profiler_Dumper_Html', 'Twig\Profiler\Dumper\HtmlDumper', false);
