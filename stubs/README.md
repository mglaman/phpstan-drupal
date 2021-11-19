# Stubs

PHPStan allows using stubs to expand information about analyzed classes via PHPDoc. 

Learn more here: https://phpstan.org/user-guide/stub-files

## Actual stubs versus shims

The PHPUnit directory does not contain actual PHPStan stubs, but rather a shim for Drupal sites without the 
`drupal/core-dev` package installed. If you would like better analysis with PHPUnit, please install the phpstan-phpunit 
extenion:

```shell
composer require --dev phpstan/phpstan-phpunit
```
