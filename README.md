<div align="center" style="text-align: center; width: 100%">
<img src="public/assets/logo/kittyshare-logo-light.svg" height="200px" alt="KittyShare Logo" />
<h1>KittyShare</h1>

A very simple file-sharing application for your server.
</div>

> [!IMPORTANT]
> This project is still under heavy development and cannot be considered "ready".

The project allows for the sharing of files and folders inside of the
filesystem. These files and folders are always readonly and return the current
state they are in.

As such the application creates a nice web interface for the files that are
intended to be distributed for other people.

## Screenshots

To see how the application looks like, it is often useful to see some screenshots:
|        Page         |                                                          Light Mode                                                           |                                                          Dark Mode                                                          |
| :-----------------: | :---------------------------------------------------------------------------------------------------------------------------: | :-------------------------------------------------------------------------------------------------------------------------: |
|    Share screen     | <img src="./screenshots/screenshot-share-light.png" alt="Share screen with some files and folders in light mode" width="300"> | <img src="./screenshots/screenshot-share-dark.png" alt="Share screen with some files and folders in dark mode" width="300"> |
|    Setup screen     | <img src="./screenshots/screenshot-setup-light.png" alt="Setup screen with username and password in light mode" width="300">  | <img src="./screenshots/screenshot-setup-dark.png" alt="Setup screen with username and password in dark mode" width="300">  |
|    Login screen     | <img src="./screenshots/screenshot-login-light.png" alt="Login screen with username and password in light mode" width="300">  | <img src="./screenshots/screenshot-login-dark.png" alt="Login screen with username and password in dark mode" width="300">  |
|    Admin screen     |     <img src="./screenshots/screenshot-admin-light.png" alt="Admin screen with list of shares in light mode" width="300">     |     <img src="./screenshots/screenshot-admin-dark.png" alt="Admin screen with list of shares in dark mode" width="300">      |
|    Browse screen    | <img src="./screenshots/screenshot-browse-light.png" alt="Browse folders to create a share screen in light mode" width="300"> | <img src="./screenshots/screenshot-browse-dark.png" alt="Browse folders to create a share screen in dark mode" width="300"> |
| Create share screen |        <img src="./screenshots/screenshot-create-share-light.png" alt="Create share screen in light mode" width="300">        |        <img src="./screenshots/screenshot-create-share-dark.png" alt="Create share screen in dark mode" width="300">        |
| Manage share screen |        <img src="./screenshots/screenshot-manage-share-light.png" alt="Manage share screen in light mode" width="300">        |        <img src="./screenshots/screenshot-manage-share-dark.png" alt="Manage share screen in dark mode" width="300">        |

## Project Structure

The project is a PHP 8.4 application with no dependencies. The project is still
using Composer to manage development dependencies and the autoloader. The
application is mainly using a SQLite database as it's backend.

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

## License

This project is licensed under the permissive [MIT-License](LICENSE).
