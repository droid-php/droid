Droid: Build, Test, CI, deploy
==============================

## Installation

Add the following line to your `composer.json`:

```json
"require": {
   "linkorb/droid": "~1.0"
}
```
Then run `composer update` to install your updated required packages.

## Usage

Create a `droid.yml` file in the root of your repository. For example:

```yml
build:
    name: "Building it"
    steps:
        composer_install:
            prefer: dist
        bower_install: ~
cs:
    requires:
        - build
    steps:
        phpcs: ~

test:
    requires:
        - build
        - cs
    steps:
        phpunit: ~
    
deploy:
    requires:
        - build
    steps:
        deploy_ssh:
            hosts: "{{ deploy.hosts }}"
            sshkey: "{{ deploy.sshkey }}"
```

At the top level, you define the "targets". When you run droid, you always pass a target name. It uses target name "build" if none is specified.

For each target, you can define a set of "steps". Each step has a task name (for example "echo") and a list of parameters for that task.

Now you can run:

```sh
vendor/bin/droid run
```

This will run the default "build" target.


## TODO / Next steps:

* [ ] Finish core classes (this is WIP!)
* [ ] Implement a set of tasks, for running composer, bower, deploy and other functionality
* [ ] Implement tasks for building docker containers

## License

MIT. Please refer to the [license file](LICENSE.md) for details.

## Brought to you by the LinkORB Engineering team

<img src="http://www.linkorb.com/d/meta/tier1/images/linkorbengineering-logo.png" width="200px" /><br />
Check out our other projects at [linkorb.com/engineering](http://www.linkorb.com/engineering).

Btw, we're hiring!
