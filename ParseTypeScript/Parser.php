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
class Parser
{

    const PARAM_UNKNOWN = 'unknown';

    /**
     * @var TypeScriptBaseInterface
     */
    private $currentInterface;

    /**
     * @var TypeScriptBaseInterface[]
     */
    private $output = [];

    /**
     * @var \ReflectionProperty[]
     */
    private $properties = [];

    public function __construct(string $filePath)
    {

        $reflectionClass = new \ReflectionClass($this->getClassFromFile($filePath));

        $this->currentInterface = new TypeScriptBaseInterface($reflectionClass->getShortName());

        if (strpos($reflectionClass->getDocComment(), '#TypeScriptMe') === false) {
            return;
        }

        $this->properties = $reflectionClass->getProperties();
        if (empty($this->properties)) {
            return;
        }

        $matches = [];

        /**
         * @var \ReflectionProperty $property
         */
        foreach ($this->properties as $property) {

            $type = $this->parsePhpDocForProperty($property);

            $isNull = '';
            if (preg_match('/nullable=true/i', $property->getDocComment(), $matches)) {
                $isNull = '?';
            }

            if (empty($isNull) && is_null($property->getType()) !== true && $property->getType()->allowsNull()) {
                $isNull = '?';
            }

            $this->currentInterface->properties[] = new TypeScriptProperty($property->getName() . $isNull, $type);

        }

        $this->output[] = $this->currentInterface;

    }

    /**
     * Obtiene el raw de la interaface de Typescript
     * @return string
     */
    public function getOutput(): string
    {
        return implode(PHP_EOL . PHP_EOL, array_map(function ($item) { return (string) $item;}, $this->output));
    }

    /**
     * Obtiene la interface que se esta usando actualmente
     *
     * @return TypeScriptBaseInterface
     */
    public function getCurrentInterface()
    {
        return $this->currentInterface;
    }

    /**
     * Obtiene el tipo de la variable en Typescript, segun el tipo de la propiedad
     *
     * @param \ReflectionProperty $property
     * @return string
     */
    private function getTypescriptPropertyByPropertyType(\ReflectionProperty $property): string
    {

        $name = $property->getType()->getName();
        $expl = explode('\\', $name);
        if (sizeof($expl) >= 2) {
            $result = end($expl);

            if ($result === 'Collection') {
                $result = $this->getRelationCollectionProperty($property->getDocComment());
            }

        } else {
            $result = $this->getTypescriptProperty($name);
        }

        return $result;

    }

    /**
     * Obtiene el tipo de la propiedad en formato Typescript, en base a los comentarios/anotaciones
     *
     * @param \ReflectionProperty $property
     * @return string
     */
    private function parsePhpDocForProperty(\ReflectionProperty $property): string
    {

        $result = self::PARAM_UNKNOWN;

        if (is_null($property->getType()) !== true) {
            return $this->getTypescriptPropertyByPropertyType($property);
        }

        if (is_null($property->getDocComment()) === true) {
            return $result;
        }

        $docComment = $property->getDocComment();

        $matches = [];
        if (preg_match('/@var (.*)/i', $docComment, $matches)) {
            if (preg_match('/@var[ \t]+([a-z0-9]+)/i', $docComment, $matches)) {
                $t = trim(strtolower($matches[1]));
                $result = $this->getTypescriptProperty($t);
            } else {
                $result = $this->getRelationProperty($docComment);
            }
        }

        if ($result === 'unknown') {
            if (preg_match('/type="([a-zA-Z]+)"/i', $docComment, $matches)) {
                $result = $this->getTypescriptProperty($matches[1]);
            } elseif (preg_match('/targetEntity=("[a-zA-Z-\\\\]+")|([a-zA-Z]+::class)/i', $docComment, $matches)) {
                $result = $this->getRelationCollectionProperty($docComment);
            }
        }

        return $result;

    }

    /**
     * En base a un tipo del tipado de la propiedad, se obtiene el correspondiente tipo en Typescript
     * @param string $type
     * @return string
     */
    private function getTypescriptProperty(string $type): string
    {

        $type = preg_replace('/[^A-Za-z0-9\-]/', '', $type);
        $type = strtolower($type);

        $result = self::PARAM_UNKNOWN;

        if (in_array($type, ['int', 'integer', 'smallint', 'bigint', 'decimal', 'float'], true)) {
            $result = 'number';
        } elseif (in_array($type, ['string', 'text', 'guid', 'date', 'time', 'datetime', 'datetimetz'])) {
            $result = 'string';
        } elseif (in_array($type, ['boolean', 'bool'])) {
            $result = 'boolean';
        } elseif (in_array($type, ['json', 'array'])) {
            $result = 'any[]';
        }

        return $result;

    }

    /**
     * Obtiene el nombre de la entidad relacionada, si esta en un comentario con el formato "@var \App\Entity\Test"
     *
     * @param string $type
     * @return string
     */
    private function getRelationProperty(string $type): string
    {

        $result = self::PARAM_UNKNOWN;
        $matches = [];

        if (preg_match('/@var \SApp\SEntity\S([a-zA-Z]+)(\[\])?/i', $type, $matches)) {

            $result = $matches[1];

            if (isset($matches[2])) {
                $result .= $matches[2];
            }
        }

        return $result;

    }

    /**
     * Obtiene el nombre de la entidad relacionada, en base a una anotación de doctrine.
     * @param string $type
     * @return string
     */
    private function getRelationCollectionProperty(string $type): string
    {

        $result = self::PARAM_UNKNOWN;

        $matches = [];

        $regex = array(
            '/targetEntity="([a-zA-Z]+)"/i',
            '/targetEntity=([a-zA-Z]+)::class/i',
            '/targetEntity="([a-zA-Z]+)\\\\([a-zA-Z]+)\\\\([a-zA-Z]+)"/i',
        );

        foreach ($regex as $reg) {
            if (preg_match($reg, $type, $matches)) {

                $collection = '[]';
                if (strpos($type, 'OneToOne') !== false || strpos($type, 'ManyToOne') !== false) {
                    $collection = '';
                }

                $result = end($matches) . $collection;
                break;
            }
        }

        return $result;

    }

    /**
     * Obtiene el namespace y nombre de clase, de un archivo PHP
     *
     * https://stackoverflow.com/a/7153391
     * @param string $file
     * @return string
     */
    private function getClassFromFile(string $file): string
    {

        $fp = fopen($file, 'r');
        $class = $namespace = $buffer = '';
        $i = 0;
        while (!$class) {
            if (feof($fp)) {
                break;
            }

            $buffer .= fread($fp, filesize($file));
            $tokens = token_get_all($buffer);

            if (strpos($buffer, '{') === false) {
                continue;
            }

            for (;$i<count($tokens);$i++) {
                if ($tokens[$i][0] === T_NAMESPACE) {
                    for ($j=$i+1;$j<count($tokens); $j++) {
                        if ($tokens[$j][0] === T_STRING) {
                            $namespace .= '\\'.$tokens[$j][1];
                        } else if ($tokens[$j] === '{' || $tokens[$j] === ';') {
                            break;
                        }
                    }
                }

                if ($tokens[$i][0] === T_CLASS) {
                    for ($j=$i+1;$j<count($tokens);$j++) {
                        if ($tokens[$j] === '{') {
                            $class = $tokens[$i+2][1];
                        }
                    }
                }
            }
        }

        return $namespace . '\\' . $class;

    }

}
