<?php
class Twig_Profiler_Dumper_Text extends Twig_Profiler_Dumper_Base
{
    protected function formatTemplate(Twig_Profiler_Profile $profile, $prefix)
    {
        return sprintf('%s└ %s', $prefix, $profile->getTemplate());
    }
    protected function formatNonTemplate(Twig_Profiler_Profile $profile, $prefix)
    {
        return sprintf('%s└ %s::%s(%s)', $prefix, $profile->getTemplate(), $profile->getType(), $profile->getName());
    }
    protected function formatTime(Twig_Profiler_Profile $profile, $percent)
    {
        return sprintf('%.2fms/%.0f%%', $profile->getDuration() * 1000, $percent);
    }
}
class_alias('Twig_Profiler_Dumper_Text', 'Twig\Profiler\Dumper\TextDumper', false);
