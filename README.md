# StellarWP Models
A library for a simple model structure.

## Table of Contents

* [Installation](#installation)
* [Notes on examples](#notes-on-examples)
* [Configuration](#configuration)
* [Creating a model](#creating-a-model)
* [Interacting with a model](#interacting-with-a-model)
* [Data transfer objects](#data-transfer-objects)
* [Classes of note](#classes-of-note)
  * [Model](#model)
  * [ModelFactory](#modelfactory)
  * [ModelQueryBuilder](#modelquerybuilder)
  * [DataTransferObject](#data-transfer-objects)
  * [Repositories\Repository](#repositoriesrepository)
* [Contracts of note](#contracts-of-note)
  * [Contracts\ModelCrud](#contractsmodelcrud)
  * [Contracts\ModelHasFactory](#contractsmodelhasfactory)
  * [Contracts\ModelReadOnly](#contractsmodelreadonly)
  * [Repositories\Contracts\Deletable](#repositoriescontractsdeletable)
  * [Repositories\Contracts\Insertable](#repositoriescontractsinsertable)
  * [Repositories\Contracts\Updatable](#repositoriescontractsupdatable)

## Installation

It's recommended that you install Schema as a project dependency via [Composer](https://getcomposer.org/):

```bash
composer require stellarwp/models
```

> We _actually_ recommend that this library gets included in your project using [Strauss](https://github.com/BrianHenryIE/strauss).
>
> Luckily, adding Strauss to your `composer.json` is only slightly more complicated than adding a typical dependency, so checkout our [strauss docs](https://github.com/stellarwp/global-docs/blob/main/docs/strauss-setup.md).

## Notes on examples

Since the recommendation is to use Strauss to prefix this library's namespaces, all examples will be using the `Boomshakalaka` namespace prefix.

## Configuration

This library requires some configuration before its classes can be used. The configuration is done via the `Config` class.

```php
use Boomshakalaka\StellarWP\Models\Config;

add_action( 'plugins_loaded', function() {
	Config::setHookPrefix( 'boom-shakalaka' );
} );
```

## Creating a model

Models are classes that hold data and provide some helper methods for interacting with that data.

### A simple model

This is an example of a model that just holds properties.

```php
namespace Boomshakalaka\Whatever;

use Boomshakalaka\StellarWP\Models\Model;

class Breakfast_Model extends Model {
	/**
	 * @inheritDoc
	 */
	protected $properties = [
		'id'        => 'int',
		'name'      => 'string',
		'price'     => 'float',
		'num_eggs'  => 'int',
		'has_bacon' => 'bool',
	];
}
```

### A CRUD model

```php
namespace Boomshakalaka\Whatever;

use Boomshakalaka\StellarWP\Models\Contracts;
use Boomshakalaka\StellarWP\Models\Model;
use Boomshakalaka\StellarWP\Models\ModelQueryBuilder;

class Breakfast_Model extends Model implements Contracts\ModelCrud {
	/**
	 * @inheritDoc
	 */
	protected $properties = [
		'id'        => 'int',
		'name'      => 'string',
		'price'     => 'float',
		'num_eggs'  => 'int',
		'has_bacon' => 'bool',
	];

	/**
	 * @inheritDoc
	 */
	public static function create( array $attributes ) : Model {
		$obj = new static( $attributes );

		return App::get( Repository::class )->insert( $obj );
	}

	/**
	 * @inheritDoc
	 */
	public static function find( $id ) : Model {
		return App::get( Repository::class )->get_by_id( $id );
	}

	/**
	 * @inheritDoc
	 */
	public function save() : Model {
		return App::get( Repository::class )->update( $this );
	}

	/**
	 * @inheritDoc
	 */
	public function delete() : bool {
		return App::get( Repository::class )->delete( $this );
	}

	/**
	 * @inheritDoc
	 */
	public static function query() : ModelQueryBuilder {
		return App::get( Repository::class )->prepare_query();
	}
}
```

## Interacting with a model

## Data transfer objects

## Classes of note

### `Model`

### `ModelFactory`

### `ModelQueryBuilder`

### `DataTransferObject`

### `Repositories\Repository`

## Contracts of note

### `Contracts\ModelCrud`

### `Contracts\ModelHasFactory`

### `Contracts\ModelReadOnly`

### `Repositories\Contracts\Deletable`

### `Repositories\Contracts\Insertable`

### `Repositories\Contracts\Updatable`
