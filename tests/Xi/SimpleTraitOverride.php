<?php
namespace Xi;

trait SimpleTraitOverride
{
    public function simple()
    {
        return parent::simple() . " overridden";
    }
}
