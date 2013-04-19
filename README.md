
# Composable mixins for PHP 5.4 #

PHP 5.4 introduced [traits](http://php.net/manual/en/language.oop5.traits.php).
In keeping with its [fractal](http://me.veekun.com/blog/2012/04/09/php-a-fractal-of-bad-design/) tradition,
it implements traits in a way that makes them basically usable but far from comfortable.

This library provides an alternative mechanism to mix in PHP 5.4 traits.
The mechanism interacts with inheritance more intuitively and dynamically, and it is less error-prone in some ways.

Due to being sort of a hack, it does add some mental burden to developers.
It's up to you to decide whether using it is a good trade-off.
It is probably suitable for use in test code, at least.


## The problem ##

Consider the following attempt at making a bunch of stackable text decoration traits:

```php
<?php
abstract class TextDecoration
{
    public function decorate($text)
    {
        return $text;
    }
}
trait Caps
{
    public function decorate($text)
    {
        return strtoupper(parent::decorate($text));
    }
}
trait Stars
{
    public function decorate($text)
    {
        return "*** " . parent::decorate($text) . " ***";
    }
}
```

Now if we try the following, we get an error due to conflicting method names: 

```php
<?php
class Foo extends TextDecoration
{
    use Caps, Stars;
}
```

## The solution ##

We can solve this in two ways: either do some renaming and wire the method calls together manually,
or make an intermediate class like this:

```php
<?php
class TextDecoration_with_Caps extends TextDecoration
{
    use Caps;
}
class Foo extends TextDecoration_with_Caps
{
    use Stars;
}
```

This "linearization of the inheritance tree" is what languages with properly implemented traits do,
and it is what this library is designed to automate. With this library, we can write:

```php
<?php
$fooClass = \Xi\ComposableMixins::extend('TextDecoration', 'Caps', 'Stars');
echo "$fooClass\n"; // Prints 'TextDecoration_with_Caps_with_Stars'

$foo = new $fooClass();
echo $foo->decorate("hello") . "\n"; // Prints '*** HELLO ***'
```

The library also permits one to override a constructor in a trait.
It's unclear why PHP normally disallows it, since constructors behave just like
regular methods in class inheritance.

## Autoloader ##

**TODO**: The library includes an autoloader that generates these classes on the fly.
When asked to load "Foo_with_Bar_with_Baz", it loads Foo, Bar and Baz and attempts to
compose them as above. This lets one write code like:

```php
<?php
class MyDeco extends TextDecoration_with_Caps_with_Stars
{
    // ...
}
```

## Namespaces ##

When doing `ComposableMixins::extend('\NS1\A', '\NS2\B')`, the generated
class will be in `\NS1` and it will be named `A_with_NS2_B`.
Those long names are somewhat unfortunate.

## IDE support ##

An obvious shortcoming of dynamically generating classes is that IDEs can't autocomplete them.
One solution is to make a (version-control-ignored) directory that the library writes these
classes to, and have the IDE treat it as a source directory.

This is enabled as follows:

```php
<?php
\Xi\ComposableMixins::setCodeWriter(new \Xi\ComposableMixins\CodeWriter('/path/to/composed_mixins));
```

## Miscellaneous ##

The first parameter to `ComposableMixins::extend` can also be a trait (or an interface) instead of a class.
It will be converted to a class automatically.

The method `ComposableMixins::instance` is like `extend` but it returns an instance of the generated class
instead of the class name. The last parameter can be an array of constructor parameters.

```php
<?php
$obj = \Xi\ComposableMixins::instance('MyTrait', 'OtherTrait', array('optionally', 'some', 'constructor', 'params'));
```

Note that, regrettably, `instanceof` never returns true for traits.
This is due to the fact that one can rename mixed in methods.
