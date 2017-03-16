## Installation

To install the library and actually use it, you've got two choices:

- **Easy way** : Require the library and autoload it using [Composer](https://getcomposer.org/). This also make the updates way more easier and version.
- **Manual way** : Download the library from github and import the files from `/src` to a `PHPOnCouch` folder in your project. You will need to update manually.

### Version

Before we get into the installation, you need to know that multiple versions are available at the moment. Since it's forked project, you have always access to the origin branches. For the future, here are the description of the available versions :

- dev-master : The lastest tested sources (Production)
- dev-develop : The lasted developments. Include new features but not tested for production.
- 2.0.0 : The PHP-on-Couch 2.0 Release (Will be installed by default)
- 1.6.1.x-dev : The PHP-on-Couch 1.6.1 production branch. This branch contains the latest developments supporting CouchDB 1.6.1.

From this information, it is up to you to choose the right version. By default, the latest release will be installed.

### Composer installation

Once you have composer installed, you are very close to have PHP-on-Couch installed. You simply need to do :
- Add the root of your project, in a command shell, execute the following command : `composer require php-on-couch/php-on-couch`. *Note: You can specify the desired version adding :2.0.0 at the end of php-on-couch.*
- After that, you have the files in `vendor/php-on-couch/phponcouch/`. To start using them, you need to either require them or use an autoloader. Thanks to composer which comes with a autoloader. Simply add this following line to import the files : `require 'path/to/vendor/autload.php';`

### Manual installation

Since you have chose the manual installation, it's a bit more complicated but still simple! As you are probably reading this, you should be on Github. First of all, you need to select the branch that you want to install. 
- Once you're on this branch, click the *Click or download* button and *Download ZIP*. 
- Within the ZIP, extract the `src` folder into a folder named `PHPOnCouch` somewhere in your project.
- The only remaning step is to require the files. You can either use your own autloader or simply require the files manually.

----

And there you go! You can use the library from there following our [documentation](README.md). Have fun!