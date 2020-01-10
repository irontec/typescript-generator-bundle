<?php
/**
 * This file is part of the TypeScriptGeneratorBundle.
 */

namespace Irontec\TypeScriptGeneratorBundle\ParseTypeScript;

/**
 * @author Irontec <info@irontec.com>
 * @author ddniel16 <ddniel16>
 * @link https://github.com/irontec
 */
class TypeScriptBaseInterface
{

    /**
     * @var string
     */
    public $name;

    /**
     * @var TypeScriptProperty[]
     */
    public $properties = [];

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function __toString()
    {
        $result = "export interface {$this->name} {\n";
        $result .= implode(",\n", array_map(function($item) { return '  ' . (string)$item;}, $this->properties));
        $result .= "\n}\n";
        return $result;
    }

}
