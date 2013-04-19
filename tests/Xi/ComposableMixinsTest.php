<?php
namespace Xi;

class ComposableMixinsTest extends \PHPUnit_Framework_TestCase
{
    public function test_overriding_trait()
    {
        $class = ComposableMixins::extend('Xi\\BaseClass', 'Xi\\OverridingTrait');
        $obj = new $class('foo');
        $this->assertOverridingTraitIsInEffect($class, $obj);
    }

    private function assertOverridingTraitIsInEffect($class, $obj)
    {
        $this->assertEquals('OverridingTrait::someMethod', $obj->someMethod());
        $this->assertEquals('OverridingTrait::someStaticMethod', $class::someStaticMethod());
        $this->assertEquals('foo_overridden', $obj->ctorParam);
    }

    public function test_with_empty_subclass_in_the_middle()
    {
        $class = ComposableMixins::extend('Xi\\EmptySubClass', 'Xi\\OverridingTrait');
        $obj = new $class('foo');
        $this->assertOverridingTraitIsInEffect($class, $obj);
    }

    public function test_stacking_two_overriding_traits()
    {
        $class = ComposableMixins::extend('Xi\\BaseClass', 'Xi\\OverridingTrait', 'Xi\\OverridingTrait2');
        $obj = new $class('foo');
        $this->assertEquals('OverridingTrait::someMethod_2', $obj->someMethod());
        $this->assertEquals('OverridingTrait::someStaticMethod_2', $class::someStaticMethod());
        $this->assertEquals('foo_overridden_twice', $obj->ctorParam);
    }

    public function test_first_parameter_of_extend_is_a_trait()
    {
        $class = ComposableMixins::extend('Xi\\SimpleTrait', 'Xi\\SimpleTraitOverride');
        $obj = new $class('foo');
        $this->assertEquals('from SimpleTrait overridden', $obj->simple());
    }

    public function test_create_instance_directly()
    {
        $obj = ComposableMixins::instance('Xi\\BaseClass', 'Xi\\OverridingTrait', array('x'));
        $this->assertEquals('OverridingTrait::someMethod', $obj->someMethod());
        $this->assertEquals('x_overridden', $obj->ctorParam);
    }

    public function test_create_instance_without_params()
    {
        $obj = ComposableMixins::instance('Xi\\SimpleTrait', 'Xi\\SimpleTraitOverride');
        $this->assertEquals('from SimpleTrait overridden', $obj->simple());
    }

    public function test_extend_interface()
    {
        $obj = ComposableMixins::instance('Xi\\SimpleInterface', 'Xi\\SimpleTrait');
        $this->assertEquals('from SimpleTrait', $obj->simple());
        $this->assertInstanceOf('Xi\\SimpleInterface', $obj);
    }

    public function test_extend_abstract_trait()
    {
        $obj = ComposableMixins::instance('Xi\\AbstractSimpleTrait', 'Xi\\SimpleTrait');
        $this->assertEquals('from SimpleTrait', $obj->simple());
    }
}