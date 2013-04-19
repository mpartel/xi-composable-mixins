<?php
namespace Xi;

trait OverridingTrait
{
    public function __construct($ctorParam)
    {
        parent::__construct($ctorParam);
        $this->ctorParam = $this->ctorParam . '_overridden';
    }

    public function someMethod()
    {
        return 'OverridingTrait::someMethod';
    }

    public static function someStaticMethod()
    {
        return 'OverridingTrait::someStaticMethod';
    }

    public function newMethod()
    {
        return 'OverridingTrait::newMethod';
    }

    public static function newStaticMethod()
    {
        return 'OverridingTrait::newStaticMethod';
    }
}