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
It allows to use filters by query parameters like following:

### EQ
```http request
GET http://host/endpoint/?type[eq]=value
GET http://host/endpoint/?type=value
```
_All examples are equal_


### IN
```http request
GET http://host/endpoint/?type[in][]=one&type[in][]=two
```

more to come...


## Usage
```php
// in DI container
$apiFilter = new ApiFilter();

// in service/controller/...
$filters = $apiFiter->parseApiFilters($request->query->all());

// in EntityRepository
$filters->modify($queryBuilder);

// or one by one
foreach ($filters as $filter) {
    $queryBuilder = $filter->modify($queryBuilder);
}


// or in raw SQL
$sql = 'SELECT * FROM table WHERE ';
$filters->append($sql);

// or one by one
foreach ($filters as $filter) {
    $sql = $filter->append($sql);
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

$filters = $apiFiter->parseApiFilters($parameters);
$sql = 'SELECT * FROM person WHERE ';

foreach ($filters as $filter) {
    $sql = $filter->append($sql);
    
    // 0. SELECT * FROM person WHERE type IN ('student', 'admin') 
    // 1. SELECT * FROM person WHERE type IN ('student', 'admin') AND name = 'Tom' 
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
