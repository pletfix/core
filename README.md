#The Pletfix Core.

Author: Frank Rohlfing <mail@frank-rohlfing.de>

##About Pletfix Core

This is the core for the Pletfix framework. 

##Core Developing

If you want to develop at the core, you can create a workbench as follows.

1. Install a fresh [Pletfix Application](https://github.com/pletfix/app)

2. Remove the Pletfix core in the vendor path: 
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

    - Delete `"pletfix/core": "dev-master"` from the `require` section
    - Add `"Core\\": "workbench/pletfix/core/src/"` to the `psr-4` autoload section.
    - Change the path of the core's function files in the `files` autoload section.
    
    After then the `autolaod` section looks like below:
    ~~~    
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