#The Pletfix Core

Author: Frank Rohlfing <mail@frank-rohlfing.de>

##About Pletfix Core

This is the core for the Pletfix framework. 

##Core Development

If you want to develop at the Pletfix Core, you can create a workbench as follows.

1. Install a fresh [Pletfix Application](https://github.com/pletfix/app)

2. Remove the Pletfix Core in the vendor path: 
    ~~~
    rm -R vendor/pletfix/core
    ~~~
    
3. Create a folder `workbench` in the project folder and clone your fork of the Pletfix Core to this folder:
    ~~~
    mkdir workbench
    cd workbench
    git clone https://github.com/pletfix/core.git
    ~~~

4. Modify `composer.json` as below:

    - Replace `"pletfix/core": "dev-master"` in the `require` section with the required packages from the core.
    - Add `"Core\\": "workbench/pletfix/core/src/"` to the `psr-4` autoload section.
    - Change the path of the core's function files in the `files` autoload section.
    
    After this the `autolaod` section looks like below:
    
    ~~~    
    "require": {
            "php": ">=5.6.4",
            "aura/sqlquery": "^2.7",
            "doctrine/cache": "^1.6",
            "fightbulc/moment": "^1.25",
            "jdorn/sql-formatter": "^1.2",
            "monolog/monolog": "~1.11",
            "vlucas/phpdotenv": "~2.2"
        },
        "require-dev": {
            "leafo/scssphp": "^0.6.6",
            "natxet/cssmin": "^3.0",
            "oyejorge/less.php": "v1.7.0.10",
            "tedivm/jshrink": "^1.1",
            "phpunit/phpunit": "^5.7",
            "npm-asset/bootstrap": "^3.3.7",
            "npm-asset/eonasdan-bootstrap-datetimepicker": "^4.17.37",
            "npm-asset/font-awesome": "^4.6.3",
            "npm-asset/jquery": "^2.2.4",
            "npm-asset/moment": "^2.10",
            "npm-asset/selectize": "^0.12.3"
        },
    "autoload": {
        "classmap": [
            "library/classes",
            "library/facades"
        ],
        "files": [
            "library/functions/helpers.php",
            "workbench/pletfix/core/functions/helpers.php",
            "workbench/pletfix/core/functions/http_status.php",
            "workbench/pletfix/core/functions/services.php"
        ],
        "psr-4": {
            "App\\": "app/",
            "Core\\": "workbench/pletfix/core/src/",
        }
    }    
    ~~~

5. Open `phpunit.xml` and modify attribute `bootstrap`:
    
    ~~~  
    <phpunit bootstrap="./workbench/pletfix/core/tests/bootstrap.php"
    ~~~