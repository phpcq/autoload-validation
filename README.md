[![Version](http://img.shields.io/packagist/v/phpcq/autoload-validation.svg?style=flat-square)](https://packagist.org/packages/phpcq/autoload-validation)
[![Stable Build Status](http://img.shields.io/travis/phpcq/autoload-validation/master.svg?style=flat-square)](https://travis-ci.org/phpcq/autoload-validation)
[![Upstream Build Status](http://img.shields.io/travis/phpcq/autoload-validation/develop.svg?style=flat-square)](https://travis-ci.org/phpcq/autoload-validation)
[![License](http://img.shields.io/packagist/l/phpcq/autoload-validation.svg?style=flat-square)](https://github.com/phpcq/autoload-validation/blob/master/LICENSE)
[![Downloads](http://img.shields.io/packagist/dt/phpcq/autoload-validation.svg?style=flat-square)](https://packagist.org/packages/phpcq/autoload-validation)

Validate the autoload information within composer.json.
=======================================================

This is useful to ensure that all classes covered by the defined autoload information in the `composer.json`.

Usage
-----

Add to your `composer.json` in the `require-dev` section:
```
"phpcq/autoload-validation": "~1.0"
```

Call the binary:
```
./vendor/bin/check-autoloading.php
```

Optionally pass the root of the git repository to check:
```
./vendor/bin/check-autoloading.php /path/to/some/repository
```
