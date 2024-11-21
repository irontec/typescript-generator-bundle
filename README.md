# TypeScriptGeneratorBundle

Este bundle genera complementeos para usar en TypeScript, basados en un proyecto symfony.

# Install

````bash
composer require irontec/typescript-generator-bundle
````

> PHP *>=8.0*

# Commands

[Generate Interface](#generate-interface)
[Generate Package](#generate-package)
[Generate All](#generate-all)

## Generate Interface

Este funcionalidad consiste, en crear interfaces de TypeScript basandose clases PHP pensadas en funcionar como entidades de doctrine.

Estas interfaces se crean teniendo en cuenta las propiedades de estas clases. Como tal se tiene 3 formas de obtener el tipo de cada propiedad.

* Definición del tipado de la propiedad fuerte, disponible desde PHP 7.4
  * > private int $id;
* Definición del tipado de la propiedad, en el comentario de esta
  * > @var int
* Definición del tipado de la propiedad, en anotaciones de doctrine
  * > @ORM\Column(type="integer")

En caso de no encontrar un tipo, se generara la interface con el tipo "**unknown**".

---

La generación de interfaces se hace ejecutando el siguiente comando:

````bash
bin/console typescript:generate:interface output-dir [entities-dir]
````

Este comando acepta 2 parametros, los cuales uno es obligatorio y otro opcional.

**output-dir** [*Obligatorio*]: Directorio donde se crearan las interfaces
**entities-dir** [*Opcional*]: Directorio de las entidades que se usaran para generar las interfaces. Por defecto se busca en "**src/Entity/**"

Para volver una entidad en una interface, es necesario escribir el comentario "**#TypeScriptMe**" o  atributo `#[TypeScriptMe]` en la definición de la clase, Ejemplo:

````php
<?php
namespace App\Entity;

...

/**
 * #TypeScriptMe
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User
{

...
````

````php
<?php
namespace App\Entity;

...

#[TypeScriptMe]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{

...
````


### Tipado en TypeScript

Listado de tipados soportados:

| TypeScript | PHP/Doctrine |
|-|-|
| number | int - integer - smallint - bigint - decimal - float |
| string | string - text - guid - date - time - datetime - datetimetz |
| boolean | boolean |
| interface | Interface de una interface relacionada en un uno a uno |
| interface[] | Array de interfaces, en una relación uno a muchos |
| unknown | Cuando no es ninguna de las anteriores |

> Si se usan las anotaciones de dotrine y se tiene definido "**nullable=true**" o en el tipado fuerte de la propiedad esta definido el ? antes del tipo, se aplica el ? despues del nombre de la propiedad, que se interpreta como un parametro optativo.

### Example

Entidad en PHP y con anotaciones.

````php
// src/Entity/User.php
<?php

namespace App\Entity;

/**
 * #TypeScriptMe
 * @ORM\Table(name="user")
 */
class User
{

   /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @var int
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=100)
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $lastname;

    /**
     * @ORM\OneToMany(targetEntity="Factory", mappedBy="author")
     */
    private \Doctrine\Common\Collections\Collection $factories;

    /**
     * @ORM\OneToOne(targetEntity="Photo", mappedBy="user")
     */
    private $photo;

....

````

Interface de TypeScript generada

````typescript
// interfaces/User.ts

export interface User {
  id: number,
  name: string,
  lastname?: string,
  enabled: boolean,
  photo: Photo,
  factories: Factory[]
}
````

Para facilitar el uso de las interfaces, se general el fichero "**models.d.ts**" en el que se hace el export de todas las interfaces.

````typescript
// interfaces/models.d.ts

export * from './User';
export * from './Photo';
export * from './Factory';
````

## Generate Package

````bash
bin/console typescript:generate:package output-dir [package-name] [version]
````

Con este comando se genera un fichero **package.json** con los datos básicos para publicar en un reposiorio privado de npm.

Cada vez que se ejecute el generador de package, por defecto se actualiza la versión "**Patch**" de este, con la opción de pasar una versión en concreto o subir de "patch", "minor" o "major".

Ejemplo del **package.json** que se genera:

````json
// interfaces/package.json
{
    "name": "@irontec/example",
    "version": "0.0.1",
    "description": "typescript interfaces for @irontec/example project",
    "types": "models.d.ts",
    "keywords": [],
    "author": "",
    "license": "EUPL"
}
````

> [Librería con la que se gestionan las versiones](https://github.com/PHLAK/SemVer)

## Generate All

````bash
bin/console typescript:generate:all output-dir [entities-dir] [package-name] [version]
````

Ejecuta los comandos anteriores.


### Publicar en un repositorio privado de NPM

Para publicar en un repositorio privado, es necesario generar previamente el fichero **package.json** y [tener instalado npm](https://github.com/nvm-sh/nvm#installing-and-updating)


1) Iniciar sesión en NPM

````bash
npm adduser --registry https://npm.example.com
````

2) Publicar/Actualizar los cambios en las interfaces

````bash
npm publish --registry https://npm.example.com
````
