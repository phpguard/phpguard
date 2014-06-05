# phpguard

Simple tool to monitor file changes, and execute command automatically.

[![License](https://poser.pugx.org/phpguard/phpguard/license.png)](https://packagist.org/packages/phpguard/phpguard)
[![Latest Stable Version](https://poser.pugx.org/phpguard/phpguard/v/stable.png)](https://packagist.org/packages/phpguard/phpguard)
[![HHVM Status](http://hhvm.h4cc.de/badge/phpguard/phpguard.png)](http://hhvm.h4cc.de/package/phpguard/phpguard)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/phpguard/phpguard/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/phpguard/phpguard/?branch=master)
[![Master Build Status](https://secure.travis-ci.org/phpguard/phpguard.png?branch=master)](http://travis-ci.org/phpguard/phpguard)
[![Coverage Status](https://coveralls.io/repos/phpguard/phpguard/badge.png?branch=master)](https://coveralls.io/r/phpguard/phpguard?branch=master)

## Installation
Using composer:
```bash
$ cd /paths/to/project
$ composer require --dev "phpguard/phpguard 0.1.*@dev"
```

## PHP Extension
At least for now `phpguard\listen` provide inotify support, so if you using linux you can run `phpguard` faster by installing `inotify` extension:
```shell
$ sudo pecl install inotify
```
And add this line to your `php.ini` file:
```
extension=inotify.so
```

## Install Plugin
By this time only 3 plugin provided by phpguard:
* Behat Plugin: https://github.com/phpguard/plugin-behat
* PhpSpec Plugin: https://github.com/phpguard/plugin-phpspec
* PHPUnit Plugin: https://github.com/phpguard/plugin-phpunit

To learn more about this plugin, please go to the plugin documentation in the related link above.
You can install this plugin by using this command:
```shell
$ cd /path/to/project
$ composer install phpguard/plugin-behat
$ composer install phpguard/plugin-phpspec
$ composer install phpguard/plugin-phpunit
```

## Running phpguard
You have to create `phpguard.yml` configuration file first, in order to run `phpguard`.
Please take a look `configuration` section below. To start phpguard just type:
```shell
$ cd /path/to/project
$ ./vendor/bin/phpguard
```
`phpguard` now will start to monitor and run command on file system events.
To run all command anytime just press `enter`.

## Configuration
### PHP Code Coverage options
`phpguard` provide coverage feature for cross testing tools. When enabled every test like `phpspec`, `behat`
and `phpunit` will be use the same code coverage collector. Available options for coverage:
```yaml
phpguard:
    coverage:
        whitelist:
            - src
        blacklist:
            - spec
            - tests
            - vendor
        show_uncovered_files:   false
        show_only_summary:      false
        output.html:            build/coverage
        output.text:            true
        output.clover:          build/logs/clover.xml
```
You can collect code coverage by using command `./vendor/bin/phpguard all --coverage`

### Ignored directories
By default `phpguard` will ignore `vendor` and also all VCS directories.
To add more ignore directories just define `ignores` options in your `phpguard.yml` file.
```yaml
phpguard:
    ignores:
        - build
        - app/cache
        - app/logs
```

### watchers
`watch` options allow you to define which files are watched by `phpguard` by using php regular expression patterns:
```yaml
# /path/to/project/phpguard.yml
phpunit:
    watch:
        - { pattern: "#^tests\/(.+)Test\.php$#" }
```
This instructs phpguard to watch for file changes in the tests folder,
but only for file names that ends with `Test.php`.

### transform
You can modify changed file name before sending it to the plugin for processing:
```yaml
phpunit:
    watch:
        - { pattern: "#^src\/(.+)\.php$#", transform: "tests/${1}Test.php }
```
`phpguard` now will use php `preg_replace` function to transform a file change in the `src` folder
to it's test case in the `tests` folder.

### Configuration Sample
```yaml
# phpguard config section
phpguard:
    ignores: build
    coverage:
        enabled: false
        whitelist:
            - src
        blacklist:
            - spec
            - tests
            - vendor
        show_uncovered_files:   false
        show_only_summary:      false
        output.html:            build/coverage
        output.text:            true
        output.clover:          build/logs/clover.xml

# phpunit config section
phpunit:
    options:
        cli:            "--colors"
        all_on_start:   true
        all_after_pass: true
        keep_failed:    true
        run_all_cli:    "--colors"
    watch:
        - { pattern: "#^src\/(.+)\.php$#", transform: "tests/functional/${1}Test.php" }
        - { pattern: "#^tests\/functional\/.*Test\.php$#" }
# phpspec config section
phpspec:
    options:
        cli:                "--format=pretty"
        all_on_start:       true
        all_after_pass:     true
        keep_failed:        true
        run_all_cli:        "--format=dot -vvv"
    watch:
        - { pattern: "#^src\/(.+)\.php$#", transform: "spec/PhpGuard/Application/${1}Spec.php" }
        - { pattern: "#^spec.*\.php$#" }
```