<div align="center" style="text-align: center; width: 100%">
<img src="public/assets/logo/kittyshare-logo-light.svg" height="200px" alt="KittyShare Logo" />
<h1>KittyShare</h1>

A very simple file-sharing application for your server.
</div>

The project allows for the sharing of files and folders inside of the
filesystem. These files and folders are always readonly and return the current
state they are in.

As such the application creates a nice web interface for the files that are
intended to be distributed for other people.

## Setup

There are two main methods of setting up the application:
1. Using Docker
2. Running it on a PHP server directly

### Running using Docker

When using Docker, you can pull the image from the
[GitHub Container Registry](https://github.com/QuickWrite/KittyShare/pkgs/container/kittyshare):

```sh
docker pull ghcr.io/quickwrite/kittyshare:latest
```

Images built from commits are tagged with the full commit SHA. Images created
from releases are tagged with the release version, such as `v1.2.0`. The
`latest` tag always points to the latest release and **not** to the latest
commit.

For configuring the application look at the section [Environment Variables](#environment-variables).

### Running directly on a PHP server

KittyShare can also be hosted directly on a server with PHP 8.4 or later. Apache is supported out of the box, although other web servers such as Nginx or Caddy can also be used.

First, clone the repository and install the Composer dependencies:

```sh
git clone https://github.com/QuickWrite/KittyShare
cd KittyShare
composer install --no-dev --optimize-autoloader
```

Configure your web server with `public/` as the document root:

```text
/path/to/KittyShare/public
```

Only the contents of the `public/` directory should be exposed to the
internet. The repository root, including `src/`, `vendor/`, environment files,
the SQLite database, and other internal files, must not be directly accessible
through the web server.

For Apache, the repository includes an example virtual host configuration in
[`apache-vhost.conf`](apache-vhost.conf). Update the paths and domain name as
needed, enable the site, and reload Apache. The important parts of the
configuration are:

```apache
DocumentRoot /path/to/KittyShare/public

<Directory /path/to/KittyShare/public>
    Options -Indexes
    AllowOverride All
    Require all granted

    FallbackResource /index.php
</Directory>
```

The `FallbackResource` directive sends application routes to `public/index.php`.
If you use Nginx, Caddy, or another web server, configure the equivalent
front-controller behavior so that requests that do not refer to an existing
file are handled by `public/index.php`.

For configuring the application look at the section [Environment Variables](#environment-variables).

### Environment variables

The application can be configured using environment variables. You can export
these variables in the service environment, configure them in your hosting
platform, or use the [`.env.example`](https://github.com/QuickWrite/KittyShare/blob/main/.env.example)
file as a reference:

| Variable                         | Description                                                            | Default            |
| -------------------------------- | ---------------------------------------------------------------------- | ------------------ |
| `KITTYSHARE_DATABASE_PATH`       | Path to the SQLite database file.                                      | `/database.sqlite` |
| `KITTYSHARE_ROOT`                | Root directory that the administrator can browse and share files from. | `/data/files`      |
| `KITTYSHARE_SESSION_LIFETIME`    | Session lifetime in seconds.                                           | `2592000`          |
| `KITTYSHARE_COOKIE_SAMESITE`     | Session cookie policy: `Lax`, `Strict`, or `None`.                     | `Lax`              |
| `KITTYSHARE_COOKIE_SECURE`       | Whether to mark the session cookie as secure.                          | Automatic          |
| `KITTYSHARE_SHOW_DOTFILES`       | Whether dotfiles should appear in file listings.                       | `false`            |
| `KITTYSHARE_BASE_URL`            | Canonical base URL used when generating absolute links.                | Relative links     |
| `KITTYSHARE_DOWNLOAD_CHUNK_SIZE` | Number of bytes sent per download chunk.                               | `8192`             |
| `KITTYSHARE_META_DESCRIPTION`    | Generic meta description text. Omitted when unset.                     | Omitted            |
| `KITTYSHARE_META_OG_MODE`        | Open Graph tags: `none`, `minimal`, or `per-share`.                    | `none`             |

Make sure the user running PHP can read the application files and has the
necessary permissions to create or modify the SQLite database. The configured
`KITTYSHARE_ROOT` directory must also be readable by the PHP process.

The `KITTYSHARE_BASE_URL` environment variable configures the application's base
path. It accepts three formats:

- **Full URL**: `https://files.example.com/app`
- **Host + path without protocol**: `127.0.0.1:3000/app`
- **Bare path**: `/app`

When set, all internal links (navigation, assets, redirects) are automatically
prefixed with the path portion. Share links displayed to users include the full
canonical URL when a full URL is provided, or the path-relative URL otherwise.

All pages send `robots: noindex, nofollow` and `referrer: no-referrer` to keep
private shares out of search indexes and to avoid leaking share URLs to external
sites. `KITTYSHARE_META_OG_MODE` controls link previews: `none` emits no `og:*`
tags (default), `minimal` emits only generic site tags, and `per-share`
additionally exposes the shared folder/file name as `og:title` (with context),
file/folder counts as `og:description`, and the app logo as `og:image` on share
pages.

## Screenshots

To see how the application looks like, it is often useful to see some screenshots:
|        Page         |                                                          Light Mode                                                           |                                                          Dark Mode                                                          |
| :-----------------: | :---------------------------------------------------------------------------------------------------------------------------: | :-------------------------------------------------------------------------------------------------------------------------: |
|    Share screen     | <img src="./screenshots/screenshot-share-light.png" alt="Share screen with some files and folders in light mode" width="300"> | <img src="./screenshots/screenshot-share-dark.png" alt="Share screen with some files and folders in dark mode" width="300"> |
|    Setup screen     | <img src="./screenshots/screenshot-setup-light.png" alt="Setup screen with username and password in light mode" width="300">  | <img src="./screenshots/screenshot-setup-dark.png" alt="Setup screen with username and password in dark mode" width="300">  |
|    Login screen     | <img src="./screenshots/screenshot-login-light.png" alt="Login screen with username and password in light mode" width="300">  | <img src="./screenshots/screenshot-login-dark.png" alt="Login screen with username and password in dark mode" width="300">  |
|    Admin screen     |     <img src="./screenshots/screenshot-admin-light.png" alt="Admin screen with list of shares in light mode" width="300">     |     <img src="./screenshots/screenshot-admin-dark.png" alt="Admin screen with list of shares in dark mode" width="300">     |
|    Browse screen    | <img src="./screenshots/screenshot-browse-light.png" alt="Browse folders to create a share screen in light mode" width="300"> | <img src="./screenshots/screenshot-browse-dark.png" alt="Browse folders to create a share screen in dark mode" width="300"> |
| Create share screen |        <img src="./screenshots/screenshot-create-share-light.png" alt="Create share screen in light mode" width="300">        |        <img src="./screenshots/screenshot-create-share-dark.png" alt="Create share screen in dark mode" width="300">        |
| Manage share screen |        <img src="./screenshots/screenshot-manage-share-light.png" alt="Manage share screen in light mode" width="300">        |        <img src="./screenshots/screenshot-manage-share-dark.png" alt="Manage share screen in dark mode" width="300">        |

## Project Structure

The project is a PHP 8.4 application with no dependencies. The project is still
using Composer to manage development dependencies and the autoloader. The
application is mainly using a SQLite database as its backend.

All files that are meant for the enduser to access are in the
[`public/`](public)-folder. This folder contains the assets and the `index.php`
as the jumping in point for the application.

Internal PHP files can be found in the [`src/`](src)-folder. It is divided into:
- `Filesystem` - The accesspoint of the application to the filesystem.
- `Http`       - Everything that has to do with the request and response. As such the Router and the response classes are in here.
- `Manager`    - The classes that do not directly access resources, but manage these based on the repositories.
- `Repository` - A simple abstraction of a specific resource. For example the SQLite database.
- `Controller` - The classes that decide on what to do with the request. They call the correct repositories, managers and return some response object.
- `Model`      - The classes that model the data that can be found in the project
- `Template`   - Templates that return HTML based on the data. They are also PHP files.

The project can also be built into a Docker container which uses PHP 8.5 with
the Apache web server.

### Creating a development environment

To create a development environment, you have to first run
```sh
composer install
```
to generate the autoloader script.

To then start the PHP development server, you can simply run
```sh
php -S 127.0.0.1:3000 -t public
```

## License

This project is licensed under the permissive [MIT-License](LICENSE).
