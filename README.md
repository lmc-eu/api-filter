Api Filter
==========

[![Latest Stable Version](https://img.shields.io/packagist/v/lmc/api-filter.svg)](https://packagist.org/packages/lmc/api-filter)
[![Build Status](https://travis-ci.org/lmc-eu/api-filter.svg?branch=master)](https://travis-ci.org/lmc-eu/api-filter)
[![Coverage Status](https://coveralls.io/repos/github/lmc-eu/api-filter/badge.svg?branch=master)](https://coveralls.io/github/lmc-eu/api-filter?branch=master)

Parser/builder for filters from api query parameters.

It is just a parser/builder for filters, it is not a place for business logic so it should be wrapped by your class, if you want to be more strict about filters.
Same if you want different settings per entity/table, it should be done by specific wrapper around this library.


## Installation
```bash
composer require lmc/api-filter
```


## Filters

### EQ (=)
```http request
GET http://host/endpoint/?field[eq]=value
GET http://host/endpoint/?field=value
```
_All examples ☝️ are equal_

### GT (>)
```http request
GET http://host/endpoint/?field[gt]=value
```

### GTE (>=)
```http request
GET http://host/endpoint/?field[gte]=value
```

### LT (<)
```http request
GET http://host/endpoint/?field[lt]=value
```

### LTE (<=)
```http request
GET http://host/endpoint/?field[lt]=value
```

### IN
```http request
GET http://host/endpoint/?type[in][]=one&type[in][]=two
```
_☝️ is not implemented yet_


## Usage
For example lets have query parameters from following request
```http request
GET http://host/endpoint/?field=value
```

### Initialization
```php
// in DI container/factory
$apiFilter = new ApiFilter();
$apiFilter->registerApplicator(...);  // optional
$apiFilter->registerEscape(...);      // optional

// in service/controller/...
$filters = $apiFiter->parseFilters($request->query->all());
```

### With basic `SQL Applicator`
```php
// in Model/EntityRepository
$sql = 'SELECT * FROM table';
$sql = $apiFilter->applyFilters($filters, $sql);    // "SELECT * FROM table WHERE 1 AND field = 'value'"

// or one by one
foreach ($filters as $filter) {
    $sql = $apiFilter->applyFilter($filter, $sql);
}
```

### With `Doctrine Applicator`
_not implemented yet_
```php
// in EntityRepository/Model
$apiFilter->applyAll($filters, $queryBuilder)

// or one by one
foreach ($filters as $filter) {
    $queryBuilder = $apiFilter->applyFilter($filter, $queryBuilder);
}
```

### Example
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

$filters = $apiFiter->parseFilters($parameters);
$sql = 'SELECT * FROM person';

foreach ($filters as $filter) {
    $sql = $apiFilter->applyFilter($filter, $sql);
    
    // 0. SELECT * FROM person WHERE 1 AND type IN ('student', 'admin') 
    // 1. SELECT * FROM person WHERE 1 AND type IN ('student', 'admin') AND name = 'Tom' 
}
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
- add prepared statements into `SqlApplicator`
    ```php
    $sql = $apiFilter->applyFilters($filters, $sql);
    $stmt = $connection->prepare($sql);
    $stmt->execute($filters->getPreparedValues());
    ```
    - (possibly remove escapes)
- allow Tuples in values
    - request with Tuple
    ```http request
    GET http://host/person/?complex-field=(first,second)
    ```
- add filters:
    - `in`
- add applicator:
    - `Doctrine\QueryBuilder`
        - prepared:
        ```php
        $queryBuilder = $apiFilter->applyFilters($filters, queryBuilder);
        $queryBuilder->setParameters($filters->getPreparedValues());
        ```
    - for "special" applicators:
        - if class exists
        - suggest in composer
- defineAllowed: (_this should be on DI level_)
    - Fields (columns)
    - Filters
    - Values
- add more examples:
    - different configuration per entity/table
