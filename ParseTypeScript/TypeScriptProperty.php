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

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $type;

    /**
     * @var bool
     */
    public $isNullable;

    public function __construct(string $name, string $type = 'unknown', bool $isNullable = false)
    {
        $this->name = $name;
        $this->type = $type;
        $this->isNullable = $isNullable;
    }

    public function __toString()
    {
        return $this->name . '?: ' . $this->type . ($this->isNullable ? ' | null' : '') ;
    }

}
