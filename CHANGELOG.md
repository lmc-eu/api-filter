# Changelog

<!-- We follow Semantic Versioning (https://semver.org/) and Keep a Changelog principles (https://keepachangelog.com/) -->
<!-- There should always be "Unreleased" section at the beginning. -->

## Unreleased
- Forbid `Value` and `Filterable` to be nested.
- Change Filter title format to allow upper case letters and underscore.
- Extends `IEnumerable` interface by `FiltersInterface` to implement both `Countable` and `IteratorAgregate` interfaces.
- Add `toArray` method to `FiltersInterface` to allow better debugging.
- Add `ApiFilterExceptionInterface` to covers all (_known_) internal exceptions.
- Fix parsing `lte` filter.
- Allow explicit filter definition in a **Tuple** _raw column_.

## 1.0.0 - 2018-08-28
- Initial version.
    - **Filters**
        - Equal to
        - Lower than 
        - Lower or equal than
        - Greater than
        - Greater or equal than
        - IN
    - **Applicators**
        - Doctrine Query Builder
        - _Naive_ SQL 
    - **Tuple** allowed in
        - Equal to
        - Lower than 
        - Lower or equal than
        - Greater than
        - Greater or equal than
