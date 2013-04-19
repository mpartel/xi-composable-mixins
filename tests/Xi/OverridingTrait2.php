<?php
namespace Xi;

trait OverridingTrait2
{
    public function __construct($ctorParam)
    {
        parent::__construct($ctorParam);
        $this->ctorParam = $this->ctorParam . '_twice';
    }

    public function someMethod()
    {
        return parent::someMethod() . '_2';
    }

    public static function someStaticMethod()
    {
        return parent::someStaticMethod() . '_2';
    }
}