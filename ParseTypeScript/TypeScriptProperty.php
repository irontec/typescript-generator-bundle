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
class TypeScriptProperty
{
    public string $name;

    public string $type;

    public bool $isNullable;

    public function __construct(string $name, string $type = 'unknown', bool $isNullable = false)
    {
        $this->name = $name;
        $this->type = $type;
        $this->isNullable = $isNullable;
    }

    public function __toString(): string
    {
        return $this->name . '?: ' . $this->type . ($this->isNullable ? ' | null' : '');
    }
}
