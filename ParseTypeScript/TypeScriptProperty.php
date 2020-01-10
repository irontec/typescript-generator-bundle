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

    public function __construct(string $name, string $type = 'unknown')
    {
        $this->name = $name;
        $this->type = $type;
    }

    public function __toString()
    {
        return $this->name . ': ' . $this->type;
    }

}
