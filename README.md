<p align="center">
  <a href="https://tur.so/turso-laravel">
    <picture>
      <img src="/.github/cover.png" alt="libSQL Laravel Adapter" />
    </picture>
  </a>
  <h1 align="center">libSQL Laravel</h1>
</p>

<p align="center">
  Databases for all Laravel Apps.
</p>

<p align="center">
  <a href="https://tur.so/turso-php"><strong>Turso</strong></a> ·
  <a href="https://docs.turso.tech"><strong>Docs</strong></a> ·
  <a href="https://docs.turso.tech/sdk/laravel/quickstart"><strong>Quickstart</strong></a> ·
  <a href="https://docs.turso.tech/sdk/laravel/reference"><strong>SDK Reference</strong></a> ·
  <a href="https://turso.tech/blog"><strong>Blog &amp; Tutorials</strong></a>
</p>

<p align="center">
  <a href="LICENSE">
    <picture>
      <img src="https://img.shields.io/github/license/tursodatabase/libsql-laravel?color=0F624B" alt="MIT License" />
    </picture>
  </a>
  <a href="https://tur.so/discord-php">
    <picture>
      <img src="https://img.shields.io/discord/933071162680958986?color=0F624B" alt="Discord" />
    </picture>
  </a>
  <a href="#contributors">
    <picture>
      <img src="https://img.shields.io/github/contributors/tursodatabase/libsql-laravel?color=0F624B" alt="Contributors" />
    </picture>
  </a>
  <a href="https://packagist.org/packages/turso/libsql-laravel">
    <picture>
      <img src="https://img.shields.io/packagist/dt/turso/libsql-laravel?color=0F624B" alt="Total downloads" />
    </picture>
  </a>
  <a href="/examples">
    <picture>
      <img src="https://img.shields.io/badge/browse-examples-0F624B" alt="Examples" />
    </picture>
  </a>
</p>

## Features

- 🔌 Works offline with [Embedded Replicas](https://docs.turso.tech/features/embedded-replicas/introduction)
- 🌎 Works with remote Turso databases (on Fly)
- ✨ Works with Turso [AI & Vector Search](https://docs.turso.tech/features/ai-and-embeddings)
- 🐘 Works with Laravel's Eloquent ORM

> [!WARNING]
> This SDK is currently in technical preview. <a href="https://tur.so/discord-laravel">Join us in Discord</a> to report any issues.

## Install

```bash
composer require turso/libsql-laravel
```

## Quickstart

Inside your Laravel application’s `config/database.php`, configure the `default` and `libsql` connections:

```php
<?php

use Illuminate\Support\Str;

return [
    "default" => env("DB_CONNECTION", "libsql"),

    "connections" => [
        "libsql" => [
            "driver" => env("DB_CONNECTION", "libsql"),
            "database" => database_path("dev.db"),
        ],

        // ...
    ],
];
```

## Documentation

Visit our [official documentation](https://docs.turso.tech/sdk/laravel).

## Support

Join us [on Discord](https://tur.so/discord-laravel) to get help using this SDK. Report security issues [via email](mailto:security@turso.tech).

## Contributors

See the [contributing guide](CONTRIBUTING.md) to learn how to get involved.

![Contributors](https://contrib.nn.ci/api?repo=tursodatabase/libsql-laravel)

<a href="https://github.com/tursodatabase/libsql-laravel/issues?q=is%3Aopen+is%3Aissue+label%3A%22good+first+issue%22">
  <picture>
    <img src="https://img.shields.io/github/issues-search/tursodatabase/libsql-laravel?label=good%20first%20issue&query=label%3A%22good%20first%20issue%22%20&color=0F624B" alt="good first issue" />
  </picture>
</a>
