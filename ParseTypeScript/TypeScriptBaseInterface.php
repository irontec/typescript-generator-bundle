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

        $imports = [];
        $pieces = [];
        foreach ($this->properties as $property) {

            if (in_array($property->type, ['number', 'string', 'boolean', 'any[]']) === false) {
                $rel = str_replace('[]', '', $property->type);
                if ($this->name !== $rel) {
                    $imports[] = 'import { ' . $rel . ' } from "./' . $rel . '";';
                }
            }

            $pieces[] = '  ' . (string) $property  . ';';
        }

        $result = "";
        $result .= implode("\n", array_unique($imports));
        $result .= "\nexport interface {$this->name} {\n";
        $result .= implode("\n", $pieces);
        $result .= "\n}\n";

        return $result;
    }

}
