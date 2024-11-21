<?php
/**
 * This file is part of the TypeScriptGeneratorBundle.
 */

namespace Irontec\TypeScriptGeneratorBundle\Package;

class Package
{
    public const PACKAGE_FILENAME = 'package.json';
    public const DEFAULT_VERSION = '0.1.0';

    public function __construct(
        private string $name,
        private string $version = self::DEFAULT_VERSION,
        private string $description = 'default',
        private string $types = 'models.d.ts',
        private string $author = '',
        private string $license = 'EUPL',
        private array  $keywords = []
    ) {
        $this->name = $name;
        $this->version = $version;
        $this->description = 'default' === $this->description ? sprintf('TypeScript interfaces for %s project', $this->name) : $description; 
        $this->types = $types;
        $this->author = $author;
        $this->license = $license;
        $this->keywords = $keywords;
    }

    public function getVersion(): ?string
    {
        if (is_string($this->version)) {
            return $this->version;
        }

        return null;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    public static function createFromJson(string $filename): self
    {
        $content = file_get_contents($filename);

        if (!is_string($content)) {
            throw new \ErrorException("Failure reading `{$filename}`");
        }

        $content = (array) json_decode($content, true);

        if (json_last_error() !== 0) {
            throw new \ErrorException(json_last_error_msg());
        }

        return new self(
            $content['name'],
            $content['version'],
            $content['description'],
            $content['types'],
            $content['author'],
            $content['license'],
            $content['keywords'],
        );
    }

    public function __toString(): string
    {
        $content = json_encode($this->toArray(), JSON_UNESCAPED_SLASHES);

        if (!is_string($content)) {
            throw new \ErrorException("Failure encoding JSON.");
        }

        return $content;
    }

    /**
     * @return array{
     *      name: string,
     *      version: string,
     *      description: string,
     *      types: string,
     *      keywords: array<string>,
     *      author: string,
     *      license: string
     * }
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'version' => $this->version,
            'description' => $this->description,
            'types' => $this->types,
            'keywords' => $this->keywords,
            'author' => $this->author,
            'license' => $this->license
        ];
    }
}
