<?php
class Twig_Extension_Profiler extends Twig_Extension
{
    private $actives = [];
    public function __construct(Twig_Profiler_Profile $profile)
    {
        $this->actives[] = $profile;
    }
    public function enter(Twig_Profiler_Profile $profile)
    {
        $this->actives[0]->addProfile($profile);
        array_unshift($this->actives, $profile);
    }
    public function leave(Twig_Profiler_Profile $profile)
    {
        $profile->leave();
        array_shift($this->actives);
        if (1 === count($this->actives)) {
            $this->actives[0]->leave();
        }
    }
    public function getNodeVisitors()
    {
        return [new Twig_Profiler_NodeVisitor_Profiler(get_class($this))];
    }
    public function getName()
    {
        return 'profiler';
    }
}
class_alias('Twig_Extension_Profiler', 'Twig\Extension\ProfilerExtension', false);
class_exists('Twig_Profiler_Profile');
