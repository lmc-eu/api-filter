API Filter
==========

[![Latest Stable Version](https://img.shields.io/packagist/v/lmc/api-filter.svg)](https://packagist.org/packages/lmc/api-filter)
[![Build Status](https://travis-ci.org/lmc-eu/api-filter.svg?branch=master)](https://travis-ci.org/lmc-eu/api-filter)
[![Coverage Status](https://coveralls.io/repos/github/lmc-eu/api-filter/badge.svg?branch=master)](https://coveralls.io/github/lmc-eu/api-filter?branch=master)

Parser/builder for filters from API query parameters.

It is just a parser/builder for filters, it is not a place for business logic so it should be wrapped by your class if you want to be more strict about filters.
Same if you want different settings per entity/table, it should be done by a specific wrapper around this library.

## Table of Contents
- [Installation](#installation)
- [Usage](#usage)
    - [Initialization](#initialization)
    - [With Doctrine Query Builder](#with-doctrine-query-builder-applicator)
    - [With simple SQL](#with-sql-applicator)
- [Supported filters](#supported-filters)
    - [Equals](#equals---eq-)
    - [Greater than](#greater-than---gt-)
    - [Greater than or Equals](#greater-than-or-equals---gte-)
    - [Lower than](#lower-than---lt-)
    - [Lower than or Equals](#lower-than-or-equals---lte-)
    - [IN](#in)
- [Tuples in filters](#tuples-in-filters)
- [Examples](#examples)
    - [IN + EQ](#in--eq-filter)
    - [GT + LT _(between)_](#gt--lt-filter-between)
    - [EQ `Tuple`](#eq-with-tuple)
- [Development](#development)

## Installation
```bash
composer require lmc/api-filter
```


## Usage
For example lets have query parameters from following request
```http request
GET http://host/endpoint/?field=value
```

### Initialization
```php
// in DI container/factory
$apiFilter = new ApiFilter();
$apiFilter->registerApplicator(...);  // optional, when you want to use non-standard implementation

// in service/controller/...
$filters = $apiFilter->parseFilters($request->query->all());

// [
//     0 => Lmc\ApiFilter\Filter\FilterWithOperator {
//         private $title    => 'eq'
//         private $operator => '='
//         private $column   => 'field'
//         private $value    => Lmc\ApiFilter\Entity\Value {
//             private $value => 'value'
//         }
//     }
// ]
```

### With Doctrine `Query Builder Applicator`
- requires `doctrine/orm` installed
- applying filters uses **cloned** `QueryBuilder` -> original `QueryBuilder` is **untouched**

#### Example
```php
// in EntityRepository/Model
$queryBuilder = $this->createQueryBuilder('alias');
$queryBuilder = $apiFilter->applyAll($filters, $queryBuilder);

// or one by one
foreach ($filters as $filter) {
    $queryBuilder = $apiFilter->applyFilter($filter, $queryBuilder);
}

// get prepared values for applied filters
$preparedValues = $apiFilter->getPreparedValues($filters, $queryBuilder); // ['field_eq' => 'value']

// get query
$queryBuilder
    ->setParameters($preparedValues)
    ->getQuery();
```

#### Shorter example (_same as ☝_)
```php
// in EntityRepository/Model
$queryBuilder = $this->createQueryBuilder('alias');

$apiFilter
    ->applyAll($filters, $queryBuilder)                                     // query builder with applied filters
    ->setParameters($apiFilter->getPreparedValues($filters, $queryBuilder)) // ['field_eq' => 'value']
    ->getQuery();
```

### With `SQL Applicator`
- ❗it is just a **naive implementation** and should be used carefully❗
- it still might be used on simple `SQL`s without `ORDER BY`, `GROUP BY` etc. because it simply adds filters as a `WHERE` conditions

`SQL Applicator` must be registered explicitly
```php
// in DI container
$apiFilter->registerApplicator(new SqlApplicator(), Priority::MEDIUM);
```

#### Example
```php
// in Model/EntityRepository
$sql = 'SELECT * FROM table';
$sql = $apiFilter->applyFilters($filters, $sql); // "SELECT * FROM table WHERE 1 AND field = :field_eq"

// or one by one
foreach ($filters as $filter) {
    $sql = $apiFilter->applyFilter($filter, $sql);
}

// get prepared values for applied filters
$preparedValues = $apiFilter->getPreparedValues($filters, $sql); // ['field_eq' => 'value']

// execute query
$stmt = $connection->prepare($sql);
$stmt->execute($preparedValues);
```

#### Shorter example (_same as ☝_)
```php
// in EntityRepository/Model
$sql = 'SELECT * FROM table';
$stmt = $connection->prepare($apiFilter->applyAll($filters, $sql)); // SELECT * FROM table WHERE 1 AND field = :field_eq 
$stmt->execute($apiFilter->getPreparedValues($filters, $sql));      // ['field_eq' => 'value']
```

## Supported filters

### Equals - EQ (=)
```http request
GET http://host/endpoint/?field[eq]=value
GET http://host/endpoint/?field=value
```
_Both examples ☝ are equal_

### Greater Than - GT (>)
```http request
GET http://host/endpoint/?field[gt]=value
```

### Greater Than Or Equals - GTE (>=)
```http request
GET http://host/endpoint/?field[gte]=value
```

### Lower Than - LT (<)
```http request
GET http://host/endpoint/?field[lt]=value
```

### Lower Than Or Equals - LTE (<=)
```http request
GET http://host/endpoint/?field[lte]=value
```

### IN
```http request
GET http://host/endpoint/?type[in][]=one&type[in][]=two
```
- `Tuples` are not allowed in `IN` filter

## `Tuples` in filters
`Tuples`
- are important in filters if you have some values, which **must** be sent together
- are composed of two or more values (_`Tuple` of one value is just a value_)
- items should be in `(` `)` and separated by `,`
- it is advised NOT to use a _space_ between values because of the _URL_ specific behavior
- for more information about `Tuples` see https://github.com/MortalFlesh/MFCollectionsPHP#immutabletuple 

### Column with `Tuple`
Columns declared by `Tuple` behaves the same as a single value but its value must be a `Tuple` as well.

### Values with `Tuple`
Values in the `Tuple` must have the same number of items as is the number of columns.

### Usage
```http request
GET http://host/endpoint/?(first,second)[eq]=(one,two) 
```
☝ means that you have two columns `first` and `second` and they must be sent together.
Column `first` must `equal` the value `"one"` and column `second` must `equal` the value `"two"`.

## Examples
❗For simplicity of examples, they are shown on the [`SQL Applicator`](#with-sql-applicator) which is NOT auto-registered❗

### `IN` + `EQ` filter
```http request
GET http://host/person/?type[in][]=student&type[in][]=admin&name=Tom
```

```php
$parameters = $request->query->all();
// [
//     "type" => [
//         "in" => [
//             0 => "student"
//             1 => "admin"
//         ]
//     ],
//     "name" => "Tom"
// ]

$filters = $apiFilter->parseFilters($parameters);
$sql = 'SELECT * FROM person';

foreach ($filters as $filter) {
    $sql = $apiFilter->applyFilter($filter, $sql);
    
    // 0. SELECT * FROM person WHERE 1 AND type IN (:type_in_0, :type_in_1) 
    // 1. SELECT * FROM person WHERE 1 AND type IN (:type_in_0, :type_in_1) AND name = :name_eq 
}

$preparedValues = $apiFilter->getPreparedValues($filters, $sql);
// [
//     'type_in_0' => 'student',
//     'type_in_1' => 'admin',
//     'name_eq'   => 'Tom',
// ]
```

### `GT` + `LT` filter (_between_)
```http request
GET http://host/person/?age[gt]=18&age[lt]=30
```

```php
$parameters = $request->query->all();
// [
//     "age" => [
//         "gt" => 18
//         "lt" => 30
//     ],
// ]

$filters = $apiFilter->parseFilters($parameters);
$sql = 'SELECT * FROM person';

$sql = $apiFilter->applyFilters($filters, $sql); // SELECT * FROM person WHERE 1 AND age > :age_gt AND age < :age_lt
$preparedValues = $apiFilter->getPreparedValues($filters, $sql); // ['age_gt' => 18, 'age_lt' => 30]
```

### `EQ` with `Tuple`
```http request
GET http://host/person/?(firstname,surname)=(John,Snow)
```

```php
$parameters = $request->query->all(); // ["(firstname,surname)" => "(John,Snow)"]

$sql = 'SELECT * FROM person';
$filters = $apiFilter->parseFilters($parameters);
// [
//     0 => Lmc\ApiFilter\Filter\FilterWithOperator {
//         private $title    => "eq"
//         private $operator => "="
//         private $column   => "firstname"
//         private $value    => Lmc\ApiFilter\Entity\Value {
//             private $value => "John"
//         }
//     },
//     1 => Lmc\ApiFilter\Filter\FilterWithOperator {
//         private $title    => "eq"
//         private $operator => "="
//         private $column   => "surname"
//         private $value    => Lmc\ApiFilter\Entity\Value {
//             private $value => "Snow"
//         }
//     }
// ]

$sql = $apiFilter->applyFilters($filters, $sql); // SELECT * FROM person WHERE 1 AND firstname = :firstname_eq AND surname = :surname_eq
$preparedValues = $apiFilter->getPreparedValues($filters, $sql); // ['firstname_eq' => 'John', 'surname_eq' => 'Snow']
```

## Development

### Install
```bash
composer install
```

### Tests
```bash
composer all
```

## Todo
- defineAllowed: (_this should be on DI level_)
    - Fields (columns)
    - Filters
    - Values
- add more examples:
    - different configuration per entity/table
