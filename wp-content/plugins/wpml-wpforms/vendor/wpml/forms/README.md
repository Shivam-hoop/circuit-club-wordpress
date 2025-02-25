# Installation

This project is meant to be used as a template for new empty project.

If you need a more comprehensive guide or you need to update an existing project, please read [Configure a WPML plugin](https://git.onthegosystems.com/wpml/wpml-plugin-template/wikis/Configure-a-WPML-plugin).

- Create a new directory (`mkdir <my-directory>`) and go into it (`cd <my-directory>`)
- Run the following commands: 
  - `git archive -o template.zip --remote=ssh://git@git.onthegosystems.com:10022/wpml/wpml-plugin-template.git master`
  - `unzip template.zip`
  - `rm template.zip`
- Finish reading this file, then run `./run.sh`. Mind that:
  - `git archive -o template.zip` will delete this file if a file with the same name exists.
  -  Running `./run.sh` you will delete this file anyway, as is not meant to be kept in your project.

# Setup

Run `./run.sh` (if you can't run Bash scripts, please open the file and manually do what's in it).  

The script will:
- Add files from `git.onthegosystems.com/wpml-shared/makefile-git-hooks`
- Setup the Git Hooks
- Install the minimal required libraries to run the CI
- Add the new files to version control

# Configuration

## plugin.php

This is the main entry point of your plugin. Modify the following with the values of your choice:

- Line #3: `My New Plugin`
- Line #4: `Add a description`
- Line #8: `my-new-plugin`
- Line #11: `MY_NEW_PLUGIN_PATH`
- Line #12: `MY_NEW_PLUGIN_URL`

After line 14, you can add the code for your plugin to work.

## Makefile

Carefully read the information at https://git.onthegosystems.com/wpml-shared/makefile-git-hooks/tree/master.  
Most likely, you don't need to change a line a here.

## Configuration files

Replace any occurrence of `my-project` and `My Project` with the slug/id/name/url of your project. In particular, in:

- `composer.json`:
  - `name` property
  - `homepage` property
- `package.json`:
  - `name` property
  - `repository` property
- `phpunit.xml`:
  - `name` attribute in the `testsuite` node
- `.gitignore`:
  - Remove the line `!/run.sh`

## WebPack/Node

Remove the dependencies and devDependencies you are not planning to use from the package.json file.

## CI

Open the `.gitlab-ci.yml`: if you are not planning to use WebPack, remove the entries annotated with "# Required only if using WebPack")

## `README.md`

Remove this file, or replace its content with something relevant to the plugin.

## `run.sh`

This file is disposable and should be removed once used.  
It will also initialize the Git repository: what is left to do is add and commit all the files as "First commit".
