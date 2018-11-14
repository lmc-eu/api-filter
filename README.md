API Filter
==========

[![Latest Stable Version](https://img.shields.io/packagist/v/lmc/api-filter.svg)](https://packagist.org/packages/lmc/api-filter)
[![Build Status](https://travis-ci.com/lmc-eu/api-filter.svg?branch=master)](https://travis-ci.com/lmc-eu/api-filter)
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
- [Exceptions and error handling](#exceptions-and-error-handling)
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
- items **must** be in `(` `)` and separated by `,`
    - `array` in `Tuple` **must** be in `[` `]` and items separated by `;`
- it is advised NOT to use a _space_ between values because of the _URL_ specific behavior
- for more information about `Tuples` see https://github.com/MortalFlesh/MFCollectionsPHP#immutabletuple 

### Column with `Tuple`
Columns declared by `Tuple` behaves the same as a single value but its value must be a `Tuple` as well.
Columns can contain a filter specification for each value.
- default filter is `EQ` for a single value and `IN` for an array of values (_in `Tuple`_)

### Values with `Tuple`
Values in the `Tuple` must have the same number of items as is the number of columns.
Values can contain a filter specification for all values in a `Tuple`.

❗**NOTE**: A filter specification **must not** be in both columns and values.

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

### More Examples

#### Equals (_implicit and explicit_)
```http request
GET http://host/person/?fullName=Jon Snow
GET http://host/person/?fullName[eq]=Jon Snow
```
Result:
```yaml
-   column: fullName
    filters: eq
    value: Jon Snow
```

#### Multiple filters (_implicit and explicit_)
By single values
```http request
GET http://host/person/?firstName=Jon&surname=Snow
GET http://host/person/?firstName[eq]=Jon&surname[eq]=Snow
```

By Tuples
```http request
GET http://host/person/?(firstName,surname)=(Jon,Snow)
GET http://host/person/?(firstName,surname)[eq]=(Jon,Snow)
GET http://host/person/?(firstName[eq],surname[eq])=(Jon,Snow)
```
Result:
```yaml
-   column: firstName
    filters: eq
    value: Jon

-   column: surname
    filters: eq
    value: Snow
```

#### Multiple filters
You can mix all types of filters (_tuples, explicit, implicit_).

##### _Perfect wife_ by generic filters
By single values
```http request
GET http://host/person/?age[gte]=18&age[lt]=30&category[in][]=serious&category[in][]=marriage&sense-of-humor=true
```

By Tuples
```http request
GET http://host/person/?(age[gte],age[lt],category,sense-of-humor)=(18,30,[serious;marriage],true)
```
Result:
```yaml
-   column: age
    filters: gte
    value: 18

-   column: age
    filters: lt
    value: 30

-   column: category
    filters: in
    value: [ serious, marriage ]

-   column: sense-of-humor
    filters: eq
    value: true
```

##### _Want to see movies_ by generic filters
By single values
```http request
GET http://host/movie/?year[gte]=2018&rating[gte]=80&genre[in][]=action&genre[in][]=fantasy
```

By Tuples
```http request
GET http://host/movie/?(year[gte],rating[gte],genre)=(2018,80,[action;fantasy])
```
Result:
```yaml
-   column: year
    filters: gte
    value: 2018

-   column: rating
    filters: gte
    value: 80

-   column: genre
    filters: in
    value: [ action, fantasy ]
```

## Exceptions and error handling

_Known_ exceptions occurring inside ApiFilter implements `Lmc\ApiFilter\Exception\ApiFilterExceptionInterface`. The exception tree is:

| Exception | Thrown when |
| ---       | ---         |
| ApiFilterExceptionInterface | Common interface of all ApiFilter exceptions |
| └ InvalidArgumentException | Base exception for assertion failed |
|   └ UnknownFilterException | Unknown filter is used in query parameters |
|   └ UnsupportedFilterableException | This exception will be thrown when no _applicator_ supports given _filterable_. |
|   └ UnsupportedFilterException | This exception should not be thrown on the client side. It is meant for developing an ApiFilter library - to ensure all Filter types are supported. |
|   └ TupleException | Common exception for all problems with a `Tuple`. It also implements `MF\Collection\Exception\TupleExceptionInterface` which might be thrown inside parsing. |

Please note if you register a custom _applicator_ to the ApiFilter (via `$apiFilter->registerApplicator()`), it may throw other exceptions which might not implement `ApiFilterExceptionInterface`.

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
