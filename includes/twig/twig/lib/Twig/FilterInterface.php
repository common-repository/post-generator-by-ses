<?php
interface Twig_FilterInterface
{
    public function compile();
    public function needsEnvironment();
    public function needsContext();
    public function getSafe(Twig_Node $filterArgs);
    public function getPreservesSafety();
    public function getPreEscape();
    public function setArguments($arguments);
    public function getArguments();
}
