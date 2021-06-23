<?php

namespace Irontec\FormlyGeneratorBundle\ParseTypeScript;

use Doctrine\Common\Annotations\AnnotationReader as DocReader;
use Doctrine\Common\Annotations\DocParser;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Helper AnnotationReader
 */
class AnnotationReader
{

    /**
     * @param $class
     * @return null|\StdClass
     */
    public static function getClass($class)
    {
        $parser = new DocParser();
        $reflector = new \ReflectionClass($class);

        $parser->setTarget(Target::TARGET_CLASS);
        $parser->setIgnoredAnnotationNamespaces([]);

        if (method_exists($reflector, 'getAttributes') && !empty($reflector->getAttributes())) {
            $annotations = [];
            foreach ($reflector->getAttributes() as $attribute) {
                $annotations[] = $attribute->newInstance();
            }
            return $annotations;
        }

        return $parser->parse($reflector->getDocComment(), 'class ' . $reflector->getName());
    }

    /**
     * @param $class
     * @param $property
     * @return array
     */
    public static function getProperty($class, $property)
    {
        $parser = new DocParser();
        $reflector = new \ReflectionProperty($class, $property);

        $class   = $reflector->getDeclaringClass();
        $context = 'property ' . $class->getName() . '::$' . $reflector->getName();

        $parser->setTarget(Target::TARGET_PROPERTY);
        $parser->setIgnoredAnnotationNamespaces([]);

        if (method_exists($reflector, 'getAttributes') && !empty($reflector->getAttributes())) {
            $annotations = [];
            foreach ($reflector->getAttributes() as $attribute) {
                $annotations[] = $attribute->newInstance();
            }
            return $annotations;
        }

        return $parser->parse($reflector->getDocComment(), $context);
    }

    /**
     * @param $class
     * @param $method
     * @return array
     */
    public static function getMethod($class, $method)
    {
        $parser = new DocParser();
        $reflector = new \ReflectionMethod($class, $method);

        $class   = $reflector->getDeclaringClass();
        $context = 'method ' . $class->getName() . '::' . $reflector->getName() . '()';

        $parser->setTarget(Target::TARGET_METHOD);
        $parser->setIgnoredAnnotationNamespaces([]);

        if (method_exists($reflector, 'getAttributes') && !empty($reflector->getAttributes())) {
            $annotations = [];
            foreach ($reflector->getAttributes() as $attribute) {
                $annotations[] = $attribute->newInstance();
            }
            return $annotations;
        }

        return $parser->parse($method->getDocComment(), $context);
    }
}
