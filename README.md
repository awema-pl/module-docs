# Docs

[![Coverage report](http://gitlab.awema.pl/awema-pl/docs/badges/master/coverage.svg)](https://www.awema.pl/)
[![Build status](http://gitlab.awema.pl/awema-pl/docs/badges/master/build.svg)](https://www.awema.pl/)
[![Composer Ready](https://www.awema.pl/awema-pl/docs/status.svg)](https://www.awema.pl/)
[![Downloads](https://www.awema.pl/awema-pl/docs/downloads.svg)](https://www.awema.pl/)
[![Last version](https://www.awema.pl/awema-pl/docs/version.svg)](https://www.awema.pl/)


Awema packages documentation reader

## Installation

Via Composer

``` bash
$ composer require awema-pl/docs
```

The package will automatically register itself.

You can publish the views with:

```bash
php artisan vendor:publish --provider="AwemaPL\Docs\DocsServiceProvider" --tag="views"
```

You can publish the config file with:

```bash
php artisan vendor:publish --provider="AwemaPL\Docs\DocsServiceProvider" --tag="config"
```

## Adding docs routes

Add to `routes\web.php`

```php
Docs::routes();
```

## Examples of use

```php
use Docs;

Docs::all($withDocs = true);

Docs::get(['package1' => ['1.0', '2.6'], 'package2' => '1.3', 'package3'], 'package4', $withDocs = true);

Docs::package('package1', $withDocs = true);

Docs::versions('package1', $withDocs = true);

Docs::version('package1', '1.0', $withDocs = true);

Docs::files('package1', '1.0', $withDocs = true);

Docs::file('package1', '1.0', 'file.md');

Docs::fileContent('package1', '1.0', 'file.md');

Docs::list();

Docs::list('package1');

Docs::list('package1', '1.0');

Docs::list('package1', '1.0', 'file.md');
```

## Methods

#### all()

`Docs::all($withDocs = true);`

Output:
```php
[
  [
    "name" => "package1",
    "versions" => [
      [ 
        "name" => "1.0",
        "files" => [
          [
            "name" => "index.md",
            "content" => "# Title"
          ],
          [
            "name" => "doc1.md",
            "content" => "Doc 1 content"
          ]
        ]
      ],
      ...
      [ 
        "name" => "2.6",
        "files" => [ ... ]
      ]
    ]
  ],
  ...  
  [
    "name" => "package4",
    "versions" => [ ... ]
  ]
]
```

Input param `$withDocs` is bollean, optional and is true by default.

if `$withDocs == false` file content will be null

#### get()

`Docs::get(['package1' => ['1.0', '2.6'], 'package2' => '1.3', 'package3'], 'package4', $withDocs = true);`

Last bool param `$withDocs` is optional and is true by default 

Method output is the same as for all(), but will contain only specified in args packages and versions.

If packages are not listed result will contain all packages.

If versions for specified package is not listed result will contain all versions for that package

### package()

`Docs::package('package1', $withDocs = true);`

Output:
```php
[
  "name" => "package1",
  "versions" => [
    [ 
      "name" => "1.0",
      "files" => [
        [
          "name" => "index.md",
          "content" => "# Title"
        ],
        [
          "name" => "doc1.md",
          "content" => "Doc 1 content"
        ]
      ]
    ],
    ...
    [ 
      "name" => "2.6",
      "files" => [ ... ]
    ]
  ]
]
```

If package is not in the docs, result will be `null`.

Last bool param `$withDocs` is optional and is true by default.

If `$withDocs == false` file `content` will be null.

### versions()

`Docs::versions('package1', $withDocs = true);`

Output:
```php
[
  [ 
    "name" => "1.0",
    "files" => [
      [
        "name" => "index.md",
        "content" => "# Title"
      ],
      [
        "name" => "doc1.md",
        "content" => "Doc 1 content"
      ]
    ]
  ],
  ...
  [ 
    "name" => "2.6",
    "files" => [ ... ]
  ]
]
```

If package is not in the docs, result will be `null`.

Last bool param `$withDocs` is optional and is true by default.

If `$withDocs == false` file `content` will be null.

### version()

`Docs::version('package1', '1.0', $withDocs = true);`

Output:
```php
[ 
  "name" => "1.0",
  "files" => [
    [
      "name" => "index.md",
      "content" => "# Title"
    ],
    [
      "name" => "doc1.md",
      "content" => "Doc 1 content"
    ]
  ]
]
```

If package or version is not in the docs, result will be `null`.

Last bool param `$withDocs` is optional and is true by default.

If `$withDocs == false` file `content` will be null.

### files()

`Docs::files('package1', '1.0', $withDocs = true);`

Output:
```php
[
  [
    "name" => "index.md",
    "content" => "# Title"
  ],
  [
    "name" => "doc1.md",
    "content" => "Doc 1 content"
  ]
]
```

If package or version is not in the docs, result will be `null`.

Last bool param `$withDocs` is optional and is true by default.

If `$withDocs == false` file `content` will be null.

### file()

`Docs::file('package1', '1.0', 'file.md');`

Output:
```php
[
  "name" => "index.md",
  "content" => "# Title"
]
```

If package or version or file is not in the docs, result will be `null`.

### fileContent()

`Docs::fileContent('package1', '1.0', 'file.md');`

Result is file content string or `null` if file does not exists

### list()

`Docs::list();`

Output is array of packages names
```php
[
  "package1",
  "package2",
  ...
  "package6"
]
```

`Docs::list('package1');`

Output is array of package versions names
```php
[
  "0.1",
  "0.2",
  ...
  "2.18"
]
```

If package is not in the docs, result will be `null`.

`Docs::list('package1', '1.0');`

Output is array of package version files names
```php
[
  "index.md",
  "doc1.md",
  ...
  "doc4.md"
]
```

If package or version is not in the docs, result will be `null`.

`Docs::list('package1', '1.0', 'file.md');`

Result is file content string or `null` if file does not exists

## Testing

You can run the tests with:

```bash
composer test
```

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email :author_email instead of using the issue tracker.

## Credits

- [Ivan Slesarenko][link-author]

## License

license. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/awema/docs.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/awema/docs.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/awema/docs/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/12345678/shield

[link-packagist]: https://packagist.org/packages/awema/docs
[link-downloads]: https://packagist.org/packages/awema/docs
[link-travis]: https://travis-ci.org/awema/docs
[link-styleci]: https://styleci.io/repos/12345678
[link-author]: https://github.com/boomdraw
[link-contributors]: ../../contributors]
