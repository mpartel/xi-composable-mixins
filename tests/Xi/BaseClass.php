<?php
namespace Xi;

class BaseClass
{
    public $ctorParam;

    public function __construct($ctorParam)
    {
        $this->ctorParam = $ctorParam;
    }

    public function someMethod()
    {
        return 'Base::someMethod';
    }

    public static function someStaticMethod()
    {
        return 'Base::someStaticMethod';
    }
}
