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

        if (!class_exists($this->getClassFromFile($filePath))) {
            return;
        }

        $source = file_get_contents($filePath);

        $tokens = token_get_all($source);
        // https://www.php.net/manual/es/tokens.php
        $comment = [T_COMMENT, T_DOC_COMMENT];

        $invalid = false;
        foreach ($tokens as $token) {
            if (is_array($token) && in_array((int) $token[0], $comment)) {
                if (strpos($token[1], 'TypeScriptMe') !== false) {
                    $invalid = true;
                    break;
                }
            }
        }

        if ($invalid === false) {
            return;
        }

        $reflectionClass = new \ReflectionClass($this->getClassFromFile($filePath));

        $this->currentInterface = new TypeScriptBaseInterface($reflectionClass->getShortName());

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
                $result = $this->getRelationCollectionProperty($property);
            }
        } else {
            $result = $this->getTypescriptProperty($name);
        }

        if (preg_match('/uuid(.*)/i', $result, $matches)) {
            $result = 'string';
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
                $result = $this->getRelationProperty($property);
            }
        }

        if ($result === 'unknown') {
            if (preg_match('/type="([a-zA-Z]+)"/i', $docComment, $matches)) {
                $result = $this->getTypescriptProperty($matches[1]);
            } elseif (preg_match('/targetEntity=("[a-zA-Z-\\\\]+")|([a-zA-Z]+::class)/i', $docComment, $matches)) {
                $result = $this->getRelationCollectionProperty($property);
            }
        }

        var_dump($property);
        var_dump($result);
        die;
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

        if (in_array($type, ['int', 'integer', 'smallint', 'bigint', 'decimal', 'float', 'datetime', 'datetimetz', 'datetimeinterface'], true)) {
            $result = 'number';
        } elseif (in_array($type, ['string', 'text', 'guid', 'date', 'time'], true)) {
            $result = 'string';
        } elseif (in_array($type, ['boolean', 'bool'], true)) {
            $result = 'boolean';
        } elseif (in_array($type, ['json'], true)) {
            $result = 'any';
        } elseif (in_array($type, ['array'], true)) {
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
    private function getRelationProperty($type): string
    {

        var_dump($type);die('getRelationProperty');

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
    private function getRelationCollectionProperty($type): string
    {

        $classRelations = [
            'Doctrine\ORM\Mapping\ManyToMany',
            'Doctrine\ORM\Mapping\OneToMany',
            'Doctrine\ORM\Mapping\ManyToOne'
        ];

        if (method_exists($type, 'getAttributes') && empty($type->getAttributes()) === false) {
            $entity = '';
            $collection = '[]';
            /** @var \ReflectionProperty $type */
            foreach ($type->getAttributes() as $att) {
                if (strpos($att->getName(), 'OneToOne') !== false || strpos($type, 'ManyToOne') !== false) {
                    $collection = '';
                }

                if (in_array($att->getName(), $classRelations)) {
                    $expl = explode('\\', $att->getArguments()['targetEntity']);
                    $entity = end($expl);
                }
            }

            // Attributes and annotations may be mixed. If the entity could not be find this way,
            // check if it's still assigned through annotations
            if (!empty($entity)) {
                return $entity . $collection;
            }
        }

        $type = $type->getDocComment();

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

        $tokens = token_get_all(file_get_contents($file));
        $count = count($tokens);

        $namespace = '';
        $i = 0;

        while ($i < $count) {
            $token = $tokens[$i];
            if (is_array($token) && $token[0] === T_NAMESPACE) {
                // Found namespace declaration
                while (++$i < $count) {
                    if ($tokens[$i] === ';') {
                        $namespace = trim($namespace);
                        break;
                    }
                    $namespace .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                }
                break;
            }
            $i++;
        }

        $classes = [];
        for ($i = 2; $i < $count; $i++) {
            if ($tokens[$i - 2][0] == T_CLASS
            && $tokens[$i - 1][0] == T_WHITESPACE
            && $tokens[$i][0] == T_STRING
            ) {
                $class_name = $tokens[$i][1];
                $classes[] = $class_name;
            }
        }

        $className = current($classes);

        return $namespace . '\\' . $className;
    }
}
