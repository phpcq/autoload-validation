[![Version](http://img.shields.io/packagist/v/contao-community-alliance/build-system-tool-autoloading-validation.svg?style=flat-square)](https://packagist.org/packages/contao-community-alliance/build-system-tool-autoloading-validation)
[![Stable Build Status](http://img.shields.io/travis/contao-community-alliance/build-system-tool-autoloading-validation/master.svg?style=flat-square)](https://travis-ci.org/contao-community-alliance/build-system-tool-autoloading-validation)
[![Upstream Build Status](http://img.shields.io/travis/contao-community-alliance/build-system-tool-autoloading-validation/develop.svg?style=flat-square)](https://travis-ci.org/contao-community-alliance/build-system-tool-autoloading-validation)
[![License](http://img.shields.io/packagist/l/contao-community-alliance/build-system-tool-autoloading-validation.svg?style=flat-square)](https://github.com/contao-community-alliance/build-system-tool-autoloading-validation/blob/master/LICENSE)
[![Downloads](http://img.shields.io/packagist/dt/contao-community-alliance/build-system-tool-autoloading-validation.svg?style=flat-square)](https://packagist.org/packages/contao-community-alliance/build-system-tool-autoloading-validation)

Validate the autoload information within composer.json.
=======================================================

This is useful to ensure that all classes covered by the defined autoload information in the `composer.json`.

Usage
-----

Add to your `composer.json` in the `require-dev` section:
```
"contao-community-alliance/build-system-tool-autoloading-validation": "~1.0"
```

Call the binary:
```
./vendor/bin/check-autoloading.php
```

Optionally pass the root of the git repository to check:
```
./vendor/bin/check-autoloading.php /path/to/some/repository
```
