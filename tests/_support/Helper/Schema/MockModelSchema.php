<?php

namespace StellarWP\Models\Tests\Schema;

use StellarWP\Models\SchemaModel;
use StellarWP\Schema\Tables\Contracts\Table_Interface;

class MockModelSchema extends SchemaModel {
	public function getTableInterface(): Table_Interface {
		return new MockModelTable();
	}
}
