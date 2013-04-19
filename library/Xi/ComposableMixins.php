<?php
namespace Xi;

use Xi\ComposableMixins\CodeWriter;

class ComposableMixins
{
    /**
     * Returns the name of a class that extends `$baseClass` and mixes in the given traits.
     *
     * Such a class is automatically created if it doesn't exist yet.
     *
     * @param string $baseClass The name of the base class.
     * @param string ... The name(s) of trait(s) to mix in.
     * @return string A name of a class.
     */
    public static function extend($baseClass)
    {
        $traits = func_get_args();
        array_shift($traits);

        $baseClass = static::fixNamespace($baseClass);
        $traits = array_map(array(get_called_class(), 'fixNamespace'), $traits);

        $baseClass = static::toClass($baseClass);

        $newClass = $baseClass;
        foreach ($traits as $trait) {
            $trait = static::fixNamespace($trait);

            $newClass = static::getCompositeClassName($baseClass, $trait);
            if (!class_exists($newClass, false)) {
                $code = static::generateCodeForCompositeClass($newClass, new \ReflectionClass($baseClass), new \ReflectionClass($trait));
                static::evalGeneratedCode($newClass, $code);
            }

            $baseClass = $newClass;
        }
        return $newClass;
    }

    /**
     * Returns an object that extends `$baseClass` and mixes in the given traits.
     *
     * If the last parameter is an array, it is treated as a list of arguments for the constructor.
     *
     * @param string $baseClass The name of the base class.
     * @param string ... The name(s) of trait(s) to mix in.
     * @param array $ctorArgs An array of constructor arguments.
     * @return object An instance of the given class with the given mixins.
     */
    public static function instance($baseClass)
    {
        $args = func_get_args();

        $last = $args[count($args)-1];
        if (is_array($last)) {
            $ctorArgs = $last;
            array_pop($args);
        } else {
            $ctorArgs = array();
        }

        $class = call_user_func_array(array(get_called_class(), 'extend'), $args);
        $refl = new \ReflectionClass($class);
        $ctor = $refl->getConstructor();
        if ($ctor) {
            return $refl->newInstanceArgs($ctorArgs);
        } else {
            return $refl->newInstance(); // Ignore ctorArgs, since that's what the 'new' operator does
        }
    }

    /**
     * Converts a trait or interface to a class.
     *
     * Does nothing if $traitName already names a class.
     *
     * @param string $traitName The name of a trait or an interface (or a class).
     * @return string The name of a class, or $traitName if it was already a class.
     */
    public static function toClass($traitName)
    {
        $code = '';

        list($ns, $traitBaseName) = static::splitOffNamespace($traitName);
        if ($ns !== '\\') {
            $nsWithoutSlash = substr($ns, 1);
            $code .= "namespace $nsWithoutSlash;\n";
        }

        $newName = $traitBaseName . "_class";
        $newNameWithNs = $ns . '\\' . $newName;

        if (class_exists($newNameWithNs)) {
            return $newNameWithNs;
        }

        $refl = new \ReflectionClass($traitName);
        if ($refl->isInterface()) {
            $code .= "abstract class $newName implements $traitBaseName {}\n";
            static::evalGeneratedCode($newNameWithNs, $code);
            return $newNameWithNs;
        } else if ($refl->isTrait()) {
            if (static::hasAbstractMethods($refl)) {
                $code .= "abstract class $newName { use $traitBaseName; }\n";
            } else {
                $code .= "class $newName { use $traitBaseName; }\n";
            }
            static::evalGeneratedCode($newNameWithNs, $code);
            return $newNameWithNs;
        } else {
            return $traitName;
        }
    }

    protected static function evalGeneratedCode($className, $code)
    {
        if (static::getCodeWriter()) {
            static::getCodeWriter()->save($className, $code);
        }
        eval($code);
    }

    /**
     * @var CodeWriter
     */
    private static $codeWriter = null;

    /**
     * @return \Xi\ComposableMixins\CodeWriter|null
     */
    public static function getCodeWriter()
    {
        return static::$codeWriter;
    }

    public static function setCodeWriter(CodeWriter $codeWriter = null)
    {
        static::$codeWriter = $codeWriter;
    }

    /**
     * Generates eval'able code that defines a class `$name` that extends `$baseClass` and mixes in `$trait`.
     *
     * @param string $name The name of the class to generate.
     * @param \ReflectionClass $baseClass The base class to inherit.
     * @param \ReflectionClass $trait The trait to mix in.
     */
    public static function generateCodeForCompositeClass($name, \ReflectionClass $baseClass, \ReflectionClass $trait)
    {
        $code = '';

        list($ns, $cls) = static::splitOffNamespace($name);
        if ($ns !== '\\') {
            $nsWithoutSlash = substr($ns, 1);
            $code .= "namespace $nsWithoutSlash;\n";
        }

        $baseClassName = '\\' . $baseClass->getName();
        $traitName = '\\' . $trait->getName();
        $traitNameUnderscores = str_replace('\\', '_', $trait->getName());

        $code .= "class $cls extends $baseClassName\n";
        $code .= "{\n";
        if (static::hasConstructor($baseClass) && static::hasConstructor($trait)) {
            $code .= "    use $traitName { $traitName::__construct as {$traitNameUnderscores}__construct; }\n";
            $code .= "    public function __construct()\n";
            $code .= "    {\n";
            $code .= "        call_user_func_array(array(\$this, '{$traitNameUnderscores}__construct'), func_get_args());\n";
            $code .= "    }\n";
        } else {
            $code .= "    use $traitName;\n";
        }
        $code .= "}\n";

        return $code;
    }

    protected static function fixNamespace($className)
    {
        if (substr($className, 0, 1) == '\\') {
            return $className;
        } else {
            return "\\$className";
        }
    }

    protected static function getCompositeClassName($baseClass, $trait)
    {
        return $baseClass . '_with' . str_replace('\\', '_', $trait);
    }

    protected static function splitOffNamespace($className)
    {
        $i = strrpos($className, '\\');
        if ($i === false) {
            return array('\\', $className);
        } else {
            return array(substr($className, 0, $i), substr($className, $i+1));
        }
    }

    protected static function hasConstructor(\ReflectionClass $cls)
    {
        if ($cls->getConstructor() !== null) {
            return true;
        } else if ($cls->getParentClass() && static::hasConstructor($cls->getParentClass())) {
            return true;
        } else {
            foreach ($cls->getTraits() as $trait) {
                if (static::hasConstructor($trait)) {
                    return true;
                }
            }
        }
        return false;
    }

    protected static function hasAbstractMethods(\ReflectionClass $cls)
    {
        $methods = $cls->getMethods(\ReflectionMethod::IS_ABSTRACT);
        return !empty($methods);
    }
}
