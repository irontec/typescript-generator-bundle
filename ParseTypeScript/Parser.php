<?php
/**
 * This file is part of the TypeScriptGeneratorBundle.
 */

namespace Irontec\TypeScriptGeneratorBundle\ParseTypeScript;

use Irontec\TypeScriptGeneratorBundle\Attribute\TypeScriptMe;
use ReflectionNamedType;

/**
 * @author Irontec <info@irontec.com>
 * @author ddniel16 <ddniel16>
 * @link https://github.com/irontec
 */
class Parser
{
    public const PARAM_UNKNOWN = 'unknown';

    private TypeScriptBaseInterface $currentInterface;

    /**
     * @var TypeScriptBaseInterface[]
     */
    private array $output = [];

    /**
     * @var \ReflectionProperty[]
     */
    private array $properties = [];

    public function __construct(string $filename)
    {
        if (!class_exists($this->getClassFromFile($filename))) {
            return;
        }

        $typeScriptMeFound = false;

        $reflectionClass = new \ReflectionClass($this->getClassFromFile($filename));

        if (0 < count($reflectionClass->getAttributes(TypeScriptMe::class))) {
            $typeScriptMeFound = true;
        } else {
            $source = file_get_contents($filename);

            if (!is_string($source)) {
                throw new \ErrorException("Failure reading `{$filename}`.");
            }

            $tokens = token_get_all($source);
            $comment = [T_COMMENT, T_DOC_COMMENT];

            foreach ($tokens as $token) {
                if (is_array($token) && in_array((int) $token[0], $comment)) {
                    if (strpos($token[1], 'TypeScriptMe') !== false) {
                        $typeScriptMeFound = true;
                        break;
                    }
                }
            }
        }

        if (false === $typeScriptMeFound) {
            return;
        }

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
            $docComment = $property->getDocComment();
            $isNull = false;

            if (preg_match('/nullable=true/i', (string) $docComment, $matches)) {
                $isNull = true;
            }

            if (empty($isNull) && is_null($property->getType()) !== true && $property->getType()->allowsNull()) {
                $isNull = true;
            }

            $this->currentInterface->properties[] = new TypeScriptProperty($property->getName(), $type, $isNull);
        }

        $this->output[] = $this->currentInterface;
    }

    /**
     * Obtiene el raw de la interaface de Typescript
     */
    public function getOutput(): string
    {
        return implode(PHP_EOL . PHP_EOL, array_map(function ($item) {return (string) $item;}, $this->output));
    }

    /**
     * Obtiene la interface que se esta usando actualmente
     */
    public function getCurrentInterface(): TypeScriptBaseInterface
    {
        return $this->currentInterface;
    }

    /**
     * Obtiene el tipo de la variable en Typescript, segun el tipo de la propiedad
     */
    private function getTypescriptPropertyByPropertyType(\ReflectionProperty $property): string
    {
        $type = $property->getType();

        if ($type instanceof ReflectionNamedType) {
            $name = $type->getName();
        } else {
            throw new \ErrorException('Unexpected type.');
        }

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
     */
    private function parsePhpDocForProperty(\ReflectionProperty $property): string
    {
        $result = self::PARAM_UNKNOWN;

        if (is_null($property->getType()) !== true) {
            return $this->getTypescriptPropertyByPropertyType($property);
        }

        $docComment = $property->getDocComment();

        if (!is_string($docComment)) {
            return $result;
        }

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

        return $result;
    }

    /**
     * En base a un tipo del tipado de la propiedad, se obtiene el correspondiente tipo en Typescript
     */
    private function getTypescriptProperty(string $type): string
    {
        $type = preg_replace('/[^A-Za-z0-9\-]/', '', $type);

        if (!is_string($type)) {
            throw new \ErrorException('Unexpected type.');
        }

        $type = strtolower($type);

        $result = self::PARAM_UNKNOWN;

        if (in_array($type, ['int', 'integer', 'smallint', 'bigint', 'decimal', 'float', 'datetime', 'datetimetz', 'datetimeinterface', 'datetimeimmutable'], true)) {
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
     */
    private function getRelationCollectionProperty(\ReflectionProperty $type): string
    {
        $classRelations = [
            'Doctrine\ORM\Mapping\ManyToMany',
            'Doctrine\ORM\Mapping\OneToMany',
            'Doctrine\ORM\Mapping\ManyToOne'
        ];

        if (empty($type->getAttributes()) === false) {
            $entity = '';
            $collection = '[]';

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

        if (!is_string($type)) {
            throw new \ErrorException('Unexpected type.');
        }

        $result = self::PARAM_UNKNOWN;

        $matches = [];

        $regex = [
            '/targetEntity="([a-zA-Z]+)"/i',
            '/targetEntity=([a-zA-Z]+)::class/i',
            '/targetEntity="([a-zA-Z]+)\\\\([a-zA-Z]+)\\\\([a-zA-Z]+)"/i',
        ];

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
     */
    private function getClassFromFile(string $filename): string
    {
        $code = file_get_contents($filename);

        if (!is_string($code)) {
            throw new \ErrorException("Failure reading `{$filename}`.");
        }

        $tokens = token_get_all($code);
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
