API Filter
==========

[![Latest Stable Version](https://img.shields.io/packagist/v/lmc/api-filter.svg)](https://packagist.org/packages/lmc/api-filter)
[![Build Status](https://travis-ci.org/lmc-eu/api-filter.svg?branch=master)](https://travis-ci.org/lmc-eu/api-filter)
[![Coverage Status](https://coveralls.io/repos/github/lmc-eu/api-filter/badge.svg?branch=master)](https://coveralls.io/github/lmc-eu/api-filter?branch=master)

Parser/builder for filters from API query parameters.

It is just a parser/builder for filters, it is not a place for business logic so it should be wrapped by your class if you want to be more strict about filters.
Same if you want different settings per entity/table, it should be done by a specific wrapper around this library.


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
//             private $value = 'value'
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

## Examples
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
- allow Tuples in values
    - request with Tuple
    ```http request
    GET http://host/person/?complex-field=(first,second)
    ```
- defineAllowed: (_this should be on DI level_)
    - Fields (columns)
    - Filters
    - Values
- add more examples:
    - different configuration per entity/table
