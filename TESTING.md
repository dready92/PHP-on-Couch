# Testing

To test the library, you needs two things:

- PHPUnit installed
- A CouchDB running.

By default, the library is binded to "http://localhost:5984". You can change the host and the port by exporting ENV variables before running the tests.

**Variables**

| Name | Type | Default | Description |
| ---- | ---- | ------- | ----------- |
| DB_HOST | String | localhost | The host of the database(ip or dns) |
| DB_PORT | Integer | 5984 | The port of the database. Note: for the moment, you can't change the port if you're using the docker image. |

# Install PHPUnit

The easy way to install PHPUnit is to use composer. In the project root, execute this :

```bash
composer install --dev
```

# Run tests

## Recommended way

PHP-on-Couch provides bash scripts that setup a CouchDB instance via docker and let you test the library. If you're on Windows, you have to install Git Bash which comes with Git when you install it.

**WARNING** : Before running the scripts, make sure the port 5984 is free. Otherwise, the docker image won't be able to run and the tests will fail. Also, if you already have a local CouchDB, it's not recommended to use it for test. Tests will interact with the database and change it's current state. 

For Windows users :

```bash
sh _runLocalWin.sh
```

For Unix/OSX users :

```bash
sh _runLocalUnix.sh
```



