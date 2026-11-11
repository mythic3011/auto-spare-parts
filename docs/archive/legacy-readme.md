> Historical reference. Paths and commands may be outdated. Use the root
> [README](../../README.md) and `./project help` for the current entrypoints.

# Auto Spare Parts

See the [transitional runtime contract](docs/api/runtime-contract.md) for the
public document root, implemented API routes, checkout behavior, required existing-database
order migration, and isolated validation commands.

## Overview

1. [For Developer](#for-developer)
2. [Database Setup](#database-setup)
3. [Git Setup](#git-setup)
4. [UI Object Naming](#ui-object-naming)
5. [Webpage Icon](#webpage-icon)
6. [PHP Script](#php-script)
7. [API](#api)
8. [Access Points](#access-points)

## For Developer

I recommend using Postman to test the API or PHP script

Postman [link](https://www.postman.com/)

## Database Setup

### Windows

Download the Docker Desktop for Windows

[Link to download](https://www.docker.com/products/docker-desktop)

### Linux

Download the Docker Desktop for Linux

[Link to download](https://www.docker.com/products/docker-desktop)

### Mac

Download the orbstack for Mac

[Link to download](https://orbstack.dev/download)

```bash
brew install orbstack
```

## Git Setup

Instructions for setting up git.

```cmd
git config --global user.name "John Doe"
git config --global user.email "johndoe@email.com"
```

## About xmapp

Instructions for using xmapp.

please use project root folder as the working directory
and use the setup.sql file as the database file on root folder

## About Docker

Instructions for using Docker.

please use project root folder as the working directory
and use the setup.sql file as the database file on sql-scripts folder

### Initialize, check and start

Run from any directory (the scripts resolve the repository root):

```sh
./scripts/init.sh
./scripts/preflight.sh
./scripts/bootstrap.sh
```

Bootstrap runs init and preflight, builds the images, installs locked PHP
packages into a dedicated dependency volume, and waits for healthy services.
Init creates a private `.env` with a random database password only when the file
is absent. Existing configuration is never overwritten. Set a nonempty
`DB_DOCKER_PASSWORD` in an existing `.env` before startup. For an existing data
volume, use its current root password: changing `.env` does not rotate database
credentials. `ENV_FILE=/absolute/path/to/config` selects an alternate file for
all three scripts. `BOOTSTRAP_TIMEOUT` controls the readiness timeout (180 seconds
by default, excluding builds).

The Compose project is named `auto-spare-parts`, independent of the checkout
folder. To reuse volumes from an older project, set `COMPOSE_PROJECT_NAME` to
that project’s existing name in `.env` before running bootstrap. Renaming the
Compose project does not migrate its volumes.

The web application is at `http://127.0.0.1:8880`; change `WEB_PORT` and
`WEB_BIND_ADDRESS` in `.env` as needed. MariaDB and shipping are internal services.
The database name remains `projectDB`, as owned by `sql-scripts/setup.sql`.
The seed runs only on an empty database volume; bootstrap never resets data or
applies migrations. Existing databases must follow the
[runtime migration contract](docs/api/runtime-contract.md).

Optional database administration (log in with database credentials):

```sh
docker compose --profile tools up -d phpmyadmin
```

phpMyAdmin is at `http://127.0.0.1:8090` by default and allows writes.
Stop the stack while retaining data and dependencies:

```sh
docker compose down
```

Use `docker compose logs --tail=100` to diagnose failed readiness. Do not use
`down -v` unless you intend to delete the database and dependency volumes.
The isolated test stack remains in `docker/compose.validation.yml`.

## UI Object Naming

Please refer to the naming convention for the object for UI

[Link to naming convention](https://jeffpar.github.io/kbarchive/kb/173/Q173738/)

## Webpage Icon

Please refer to the icon of the webpage

[Link to icons](https://heroicons.com/)

## PHP Script

Instructions for connecting to the database.

```php
// Add your PHP database connection script here
```

## API

Instructions for using the API, including dev token, login details, and endpoints.

### Dev Token

```
aab012e9-ae5d-4c74-adc0-ce4f9b9b7d8c
```

To Test, it

```
http://127.0.0.1:8880/resources/php/api.php
```

### API Usage in HTML/JavaScript

Include the following JavaScript function in your HTML file to make API calls:

```javascript
async function callApi(action, data = {}) {
  const url = "http://127.0.0.1:8880/resources/php/api.php";
  const token = localStorage.getItem("token"); // Assuming token storage in localStorage

  try {
    const response = await fetch(`${url}?action=${action}`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: token,
      },
      body: JSON.stringify(data),
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    return await response.json();
  } catch (error) {
    console.error("API call failed:", error);
    throw error;
  }
}
```

### Login

```javascript
async function login(username, password) {
  try {
    const result = await callApi("login", { username, password });
    if (result.result && result.result.token) {
      localStorage.setItem("token", result.result.token);
      console.log("Login successful");
    } else {
      console.error("Login failed");
    }
  } catch (error) {
    console.error("Login error:", error);
  }
}
```

#### Dealer Account

username: `alex@auto-racing.com`
password: `DemoParts!2026`

#### Sales Manager Account

username: `kit@slms.com`
password: `DemoParts!2026`

### Get Spare Part List

```javascript
async function getSparePartList() {
  try {
    const result = await callApi("getSparePartList");
    if (result.result) {
      displaySparePartList(result.result);
    }
  } catch (error) {
    console.error("Error fetching spare part list:", error);
  }
}
```

### Other API Actions

For other API actions like updating dealer profile, adding orders, etc., follow a similar pattern:

```javascript
async function performAction(actionName, actionData) {
  try {
    const result = await callApi(actionName, actionData);
    console.log(`${actionName} result:`, result);
  } catch (error) {
    console.error(`Error in ${actionName}:`, error);
  }
}
```

## Access Points

### Web Application

Access the web application at:

```
http://127.0.0.1:8880/
```

### Database administration (optional)

With the tools profile enabled, access phpMyAdmin at:

```
http://127.0.0.1:8090/
```

### API

Shipping is internal to the Compose network at `http://api:8080`.

Database administration permits writes; use care with existing data.

### How to use the Cart API

#### Create a new cart

```js

```

## resume the claude

```bash
claude --resume 4570c6c4-6ba0-4481-b649-5f8df0f5b409
```
