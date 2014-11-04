[![Version](http://img.shields.io/packagist/v/contao-community-alliance/build-system-tool-branch-alias-validation.svg?style=flat-square)](https://packagist.org/packages/contao-community-alliance/build-system-tool-branch-alias-validation)
[![Stable Build Status](http://img.shields.io/travis/contao-community-alliance/build-system-tool-branch-alias-validation/master.svg?style=flat-square)](https://travis-ci.org/contao-community-alliance/build-system-tool-branch-alias-validation)
[![Upstream Build Status](http://img.shields.io/travis/contao-community-alliance/build-system-tool-branch-alias-validation/develop.svg?style=flat-square)](https://travis-ci.org/contao-community-alliance/build-system-tool-branch-alias-validation)
[![License](http://img.shields.io/packagist/l/contao-community-alliance/build-system-tool-branch-alias-validation.svg?style=flat-square)](https://github.com/contao-community-alliance/build-system-tool-branch-alias-validation/blob/master/LICENSE)
[![Downloads](http://img.shields.io/packagist/dt/contao-community-alliance/build-system-tool-branch-alias-validation.svg?style=flat-square)](https://packagist.org/packages/contao-community-alliance/build-system-tool-branch-alias-validation)

Validate branch alias against latest tag.
=========================================

This is useful to ensure that no branch alias is "behind" the most recent tag on the given branch for the alias.

Usage
-----

Add to your `composer.json` in the `require-dev` section:
```
"contao-community-alliance/build-system-tool-branch-alias-validation": "~1.0"
```

Call the binary:
```
./vendor/bin/validate-branch-alias.php
```

Optionally pass the root of the git repository to check:
```
./vendor/bin/validate-branch-alias.php /path/to/some/git/repository
```
