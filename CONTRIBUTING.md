# Contributing to arkitect

How Can I Contribute?

## Reporting Bugs

To report bugs you can open an issue on this repository. Please provide as much information as you can to help discover and fix the bug.
Useful information are:
- Which PHP version are you running?
- Which problem are you experiencing?

If possible, a test highlihting the bug would be great.
If you are fixing a bug, create a pull request, linking the issue with bug's details (if there is any) and provide tests.
The build must be green for the PR being merged.

## Suggesting Enhancements

If you want to propose an enhancements open an issue explaining why you think it would be useful.
Once you get a green light implement stuff, create a PR. Remember to provide tests.
The build must be green for the PR being merged.

## How to develop arkitect

In order to fix a bug or submit a new enhancement we suggest to run the build locally or using docker (with the dockerfile provided).
Some common tasks are available in the Makefile file (you still can use it to see how run things even your system does not support make).

To create the docker image and then enter the docker container shell:

```shell
docker image build -t phparkitect .
docker run --rm -it --entrypoint= -v $(PWD):/arkitect phparkitect bash
```

If you prefer use more shorter make commands (use `make` without arguments for help):

```shell
make dbi
make shell
```

The first time, after the docker container has been created, remember to install the packages with composer:

```shell
composer install
```
