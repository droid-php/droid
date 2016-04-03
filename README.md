Droid: Build, Test, CI, deploy
==============================

<img src="http://2.bp.blogspot.com/_XdP6Lp2ceqY/TP8P368BzMI/AAAAAAAAiZ0/gdZN-geaN94/s1600/t3.PNG" style="width: 100%" />

Droid is a task-runner, similar to [Apache Ant](http://ant.apache.org/), [Gulp](http://gulpjs.com/), [Grunt](http://gruntjs.com/), [Phing](https://www.phing.info/), etc.

It helps you to automatically build your project, compile assets, create directory structures, run and validate tests, setup fixtures, and eventually deploy your app.

Tasks are implemented as standard [Symfony Commands](http://symfony.com/doc/current/cookbook/console/console_command.html), allowing you to use standard existing commands to script your builds.



## Installation

Add the following line to your `composer.json`:

```json
"require": {
   "droid/droid": "~1.0"
}
```
Then run `composer update` to install your updated required packages.

## Configuring droid for your project

Create a `droid.yml` file in the root of your repository. For example:

```yml
targets:
   default:
      name: "Building it"
      steps:
         - "composer:install":
            prefer: dist
         - "bower:install": ~
   cs:
      requires:
         - default
      steps:
         - "phpcs:check": ~

   test:
      requires:
         - build
         - cs
      steps:
         - "phpunit:test": ~
    
   deploy:
      requires:
         - build
      loop:
         -
            host: app1.example.com
            port: 22
         -
            host: app2.example.com
            port: 2222

      steps:
         - "deploy:ssh":
            sshkey: "{{ deploy.sshkey }}"
            basepath: "/code/myapp/"
```

At the top level, you define the "targets". When you run droid, you always pass a target name. It uses target name "default" if none is specified.

For each target, you can define a set of "steps". Each step has a command name (for example "core:echo") and a list of arguments for that command.

## Running droid

### Running a target

```sh
vendor/bin/droid run
```

This will run the "default" target.

Alternatively, you can specify which target to run:
```sh
vendor/bin/droid run test
```

## Listing available commands

```sh
vendor/bin/droid list
```
This lists the available commands you can use in your `droid.yml` file.

## Registering your own custom commands

To register custom commands, you can add the following section to your droid.yml:

```yml
register:
    - Haigha\Command\LoadCommand: ~
```

When you list the available commands, you'll find that [Haiga's](http://github.com/linkorb/haigha) `fixture:load` command has been added to the available command list. You can now use it as a command in your target steps.

If you'd like to change the default 'name' for the registered command, simply pass a name parameter:

```yml
register:
    - Haigha\Command\LoadCommand:
        name: "haigha:load"
```

## Creating plugins for Droid

Droid plugins are composer packages that can automatically register their commands in droid.
The consuming application won't need to use the `register` key in their `droid.yml`.

You can easily turn your php package into a Droid plugin by including a class called `DroidPlugin` in the root of your library's PHP namespace. For example, if your project's namespace is 'Acme\\MyApp', you can add a file in the root of that namespace called 'DroidPlugin' like this:

```php
<?php

namespace Acme\MyApp;

class DroidPlugin
{
    public function __construct($droid)
    {
        $this->droid = $droid;
    }
    
    public function getCommands()
    {
        $commands = [];
        $commands[] = new \Acme\MyApp\Command\MyFirstCommand();
        $commands[] = new \Acme\MyApp\Command\MySecondCommand();
        return $commands;
    }
}
```
The constructor gets access to the `Droid\\Application` instance. You'll need to implement a method
called `getCommands` that returns a list of Symfony Console Commands.

After including both droid/droid and your custom package in a project, you'll have access to all of the 
default Droid commands, and all the commands returned by your `DroidPlugin::getCommands` method.


## Get information about a command

To see how you can use a command, run:

```sh
vendor/bin/droid help core:echo
```
This will list the available arguments and options available in the `core:echo` command.

You can simply add these arguments in your `droid.yml` file like this:

```yml
   steps:
      - "core:echo":
         message: "Hello world from the echo task!"
      - "core:echo":
         message: "This should be in red"
         color: "red"
```

When you're trying to use a command, and forget to specify any *required* arguments, droid will tell you what's missing.

## TODO / Next steps:

* [ ] Finish core classes (this is WIP!)
* [ ] Implement a set of commands, for running composer, bower, deploy and other functionality
* [ ] Implement tasks for building docker containers

## License

MIT. Please refer to the [license file](LICENSE.md) for details.

## Brought to you by the LinkORB Engineering team

<img src="http://www.linkorb.com/d/meta/tier1/images/linkorbengineering-logo.png" width="200px" /><br />
Check out our other projects at [linkorb.com/engineering](http://www.linkorb.com/engineering).

Btw, we're hiring!
