# phpguard

Simple tool to monitor file changes, and execute command automatically.

[![License](https://poser.pugx.org/phpguard/phpguard/license.png)](https://packagist.org/packages/phpguard/phpguard)
[![Latest Stable Version](https://poser.pugx.org/phpguard/phpguard/v/stable.png)](https://packagist.org/packages/phpguard/phpguard)
[![HHVM Status](http://hhvm.h4cc.de/badge/phpguard/phpguard.png)](http://hhvm.h4cc.de/package/phpguard/phpguard)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/phpguard/phpguard/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/phpguard/phpguard/?branch=master)
[![Master Build Status](https://secure.travis-ci.org/phpguard/phpguard.png?branch=master)](http://travis-ci.org/phpguard/phpguard)
[![Coverage Status](https://coveralls.io/repos/phpguard/phpguard/badge.png?branch=master)](https://coveralls.io/r/phpguard/phpguard?branch=master)

# Installation
Using composer:
```bash
$ cd /paths/to/project
$ composer require --dev "phpguard/phpguard 0.1.*@dev"
```

# Inotify Requirement
At least for now `phpguard\listen` provide inotify support, so if you using linux you can run `phpguard` faster by installing `inotify` extension:
```bash
$ sudo pecl install inotify
```
And add this line to your `php.ini` file:
```
extension=inotify.so
```

# PhpSpec Processor

```yaml
# /path/to/project/phpguard.yml
phpspec:
    options:
        format:             pretty  # run phpspec with pretty format
        ansi:               true    # force phpspec to use ansi color
        all_after_pass:     true    # run all spec after run spec file success
        run_all:
            format: progress        # use progress format when running all spec
    watch:
        # watch src/Namespace/Class.php for changes and ask phpguard to run phpspec
        # with file name "spec/Namespace/ClassSpec.php"
        - { pattern: "#^src\/(.+)\.php$#", transform: "spec/${1}Spec.php" }
        # watch file in spec directory and ask phpguard to run phpspec for that file
        - { pattern: "#^spec.*\.php$#" }
```

# PHPUnit Processor

```yaml
# /path/to/project/phpguard.yml
phpunit:
    options:
        cli: "--exclude-group phpspec"  # set argument and options for phpunit cli
        all_after_pass: true            # run all tests after pass
    watch:
        # watch src/PhpGuard/Application/Class.php for changes and ask phpguard to run phpunit
        # with file name "tests/PhpGuard/Application/Tests/ClassTest.php"
        - { pattern: "#^src\/PhpGuard\/Application\/(.+)\.php$#", transform: "tests/PhpGuard/Application/Tests/${1}Test.php" }
        # watch file in tests directory and ask phpguard to run phpunit for that file
        - { pattern: "#^tests\/.*Test\.php$#" }
```

# Running PhpGuard

```bash
$ cd /path/to/project
$ ./vendor/bin/phpguard
```
PhpGuard will run commands automatically when you change your source file.
If you need to run all tests, just press `enter`.