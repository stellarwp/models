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

### A ReadOnly model

This is a model whose intent is to only read and store data. The Read operations should - in most cases - be deferred to
a repository class, but the model should provide a simple interface for interacting with the repository. You can create
ReadOnly model by implementing the `Contracts\ModelReadOnly` contract.

```php
namespace Boomshakalaka\Whatever;

use Boomshakalaka\StellarWP\Models\Contracts;
use Boomshakalaka\StellarWP\Models\Model;
use Boomshakalaka\StellarWP\Models\ModelQueryBuilder;

class Breakfast_Model extends Model implements Contracts\ModelReadOnly {
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
	public static function find( $id ) : Model {
		return App::get( Repository::class )->get_by_id( $id );
	}

	/**
	 * @inheritDoc
	 */
	public static function query() : ModelQueryBuilder {
		return App::get( Repository::class )->prepare_query();
	}
}
```

### A CRUD model

This is a model that includes CRUD operations. Ideally, the actual CRUD operations should be deferred to and handled by
a repository class, but the model should provide a simple interface for interacting with the repository. We get a CRUD
model by implementing the `Contracts\ModelCrud` contract.

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
		return App::get( Repository::class )->prepareQuery();
	}
}
```

## Attribute validation

Sometimes it would be helpful to validate attributes that are set in the model. To do that, you can create `validate_*()`
methods that will execute any time an attribute is set.

Here's an example:

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

	/**
	 * Validate the name.
	 *
	 * @param string $value
	 *
	 * @return bool
	 */
	public function validate_name( $value ): bool {
		if ( ! preg_match( '/eggs/i', $value ) ) {
			throw new \Exception( 'Breakfasts must have "eggs" in the name!' );
		}

		return true;
	}
}

```

## Data Transfer Objects

Data Transfer Objects (DTOs) are classes that help with the translation of database query results (or other sources of data)
into models. DTOs are not required for using this library, but they are recommended. Using these objects helps you be more
deliberate with your query usage and allows your models and repositories well with the `ModelQueryBuilder`.

Here's an example of a DTO for breakfasts:

```php
namespace Boomshakalaka\Whatever;

use Boomshakalaka\Whatever\StellarWP\Models\DataTransferObject;
use Boomshakalaka\Whatever\Breakfast_Model;

class Breakfast_DTO extends DataTransferObject {
	/**
	 * Breakfast ID.
	 *
	 * @var int
	 */
	 public int $id;

	/**
	 * Breakfast name.
	 *
	 * @var string
	 */
	 public string $name;

	/**
	 * Breakfast price.
	 *
	 * @var float
	 */
	 public float $price;

	/**
	 * Number of eggs in the breakfast.
	 *
	 * @var int
	 */
	 public int $num_eggs;

	/**
	 * Whether or not the breakfast has bacon.
	 *
	 * @var bool
	 */
	 public bool $has_bacon;

	/**
	 * Builds a new DTO from an object.
	 *
	 * @since TBD
	 *
	 * @param object $object The object to build the DTO from.
	 *
	 * @return Breakfast_DTO The DTO instance.
	 */
	public static function fromObject( $object ): self {
		$self = new self();

		$self->id        = $object->id;
		$self->name      = $object->name;
		$self->price     = $object->price;
		$self->num_eggs  = $object->num_eggs;
		$self->has_bacon = (bool) $object->has_bacon;

		return $self;
	}

	/**
	 * Builds a model instance from the DTO.
	 *
	 * @since TBD
	 *
	 * @return Breakfast_Model The model instance.
	 */
	public function toModel(): Breakfast_Model {
		$attributes = get_object_vars( $this );

		return new Breakfast_Model( $attributes );
	}
}
```

## Repositories

Repositories are classes that fetch from and interact with the database. Ideally, repositories would be used to
query the database in different ways and return corresponding models. With this library, we provide
`Deletable`, `Insertable`, and `Updatable` contracts that can be used to indicate what operations a repository provides.

You may be wondering why there isn't a `Findable` or `Readable` contract (or similar). That's because the fetching needs
of a repository varies with the usecase. However, in the `Repository` abstract class, there is an abstract `prepareQuery()`
method. This method should return a `ModelQueryBuilder` instance that can be used to fetch data from the database.

```php
namespace Boomshakalaka\Whatever;

use Boomshakalaka\StellarWP\Models\Contracts\Model;
use Boomshakalaka\StellarWP\Models\ModelQueryBuilder;
use Boomshakalaka\StellarWP\Repositories\Repository;
use Boomshakalaka\StellarWP\Repositories\Contracts;
use Boomshakalaka\Whatever\Breakfast_Model;
use Boomshakalaka\Whatever\Breakfast as Table;

class Breakfast_Repository extends Repository implements Contracts\Deletable, Contracts\Insertable, Contracts\Updatable {
	/**
	 * {@inheritDoc}
	 */
	public function delete( Model $model ): bool {
		return (bool) DB::delete( Table::table_name(), [ 'id' => $model->id ], [ '%d' ] );
	}

	/**
	 * {@inheritDoc}
	 */
	public function insert( Model $model ): Breakfast_Model {
		DB::insert( Table::table_name(), [
			'name' => $model->name,
			'price' => $model->price,
			'num_eggs' => $model->num_eggs,
			'has_bacon' => (int) $model->has_bacon,
		], [
			'%s',
			'%s',
			'%d',
			'%d',
		] );

		$model->id = DB::last_insert_id();

		return $model;
	}

	/**
	 * {@inheritDoc}
	 */
	function prepareQuery(): ModelQueryBuilder {
		$builder = new ModelQueryBuilder( Breakfast_Model::class );

		return $builder->from( Table::table_name( false ) );
	}

	/**
	 * {@inheritDoc}
	 */
	public function update( Model $model ): Model {
		DB::update( Table::table_name(), [
			'name' => $model->name,
			'price' => $model->price,
			'num_eggs' => $model->num_eggs,
			'has_bacon' => (int) $model->has_bacon,
		], [ 'id' => $model->id ], [
			'%s',
			'%s',
			'%d',
			'%d',
		], [ '%d' ] );

		return $model;
	}

	/**
	 * Finds a Breakfast by its ID.
	 *
	 * @since TBD
	 *
	 * @param int $id The ID of the Breakfast to find.
	 *
	 * @return Breakfast_Model|null The Breakfast model instance, or null if not found.
	 */
	public function find_by_id( int $id ): ?Breakfast_Model {
		return $this->prepareQuery()->where( 'id', $id )->get();
	}
}
```

### Interacting with the Repository

### Querying

```php
$breakfast = App::get( Breakfast_Repository::class )->find_by_id( 1 );

// Or, we can fetch via the model, which defers to the repository.
$breakfast = Breakfast_Model::find( 1 );
```

### Inserting

```php
$breakfast = new Breakfast_Model( [
	'name'      => 'Bacon and Eggs',
	'price'     => 5.99,
	'num_eggs'  => 2,
	'has_bacon' => true,
] );

$breakfast->save();
```

### Updating

```php
$breakfast = Breakfast_Model::find( 1 );
$breakfast->setAttribute( 'price', 6.99 );
$breakfast->save();
```

### Deleting

```php
$breakfast = Breakfast_Model::find( 1 );
$breakfast->delete();
```

## Classes of note

### `Model`

This is an abstract class to extend for your models.

### `ModelFactory`

This is an abstract class to extend for creating model factories.

### `ModelQueryBuilder`

This class extends the [`stellarwp/db`](https://github.com/stellarwp/db) `QueryBuilder` class so that it returns
model instances rather than arrays or `stdClass` instances. Using this requires models that implement the `ModelFromQueryBuilderObject`
interface.

### `DataTransferObject`

This is an abstract class to extend for your DTOs.

### `Repositories\Repository`

This is an abstract class to extend for your repositories.

## Contracts of note

### `Contracts\ModelCrud`

Provides definitions of methods for CRUD operations in a model.

### `Contracts\ModelHasFactory`

Provides definition for factory methods within a model.

### `Contracts\ModelReadOnly`

Provides method signatures for read operations in a model.

### `Repositories\Contracts\Deletable`

Provides method signatures for delete methods in a repository.

### `Repositories\Contracts\Insertable`

Provides method signatures for insert methods in a repository.

### `Repositories\Contracts\Updatable`

Provides method signatures for update methods in a repository.
