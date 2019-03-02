# Pretty Helpful PHP Coding Standard

* [Introduction](#introduction)
* [Installation](#installation)
* [How to use](#how-to-use)
* [Contributing](#contributing)
* [License](#license)

## Introduction

The Pretty Helpful PHP coding standard is a [PSR-2-based](https://www.php-fig.org/psr/psr-2/) coding standard built for use with [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer).

This coding standard was written with the following basic principles in mind:
1) Be consistent.
2) Keep functions simple and easy to read.
3) Avoid practices that lead to unreliable code or unreliable tests.

## Installation

This standard is configured for use with [Composer](http://getcomposer.org).

To install it as a dependency, run the following command:
```
composer require pretty-helpful-php/coding-standard --dev
```

## How to use

Once the standard is installed, it can be run from command line using the following command:

```
phpcs --standard=PrettyHelpfulPHP /path/to/somefile.php
```

See the [PHP_CodeSniffer usage page](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Usage) for more information.

## Contributing

Contributions are welcome. See [contributing](.github/CONTRIBUTING.md) for more information.

## License

This coding standard is published under the [MIT](LICENSE) license.
