# Contributing to arkitect

How Can I Contribute?

## Reporting Bugs

To report bugs you can open an issue on this repository. Please provide as much information as you can to help discover and fix the bug.
Useful information includes:
- Which PHP version are you running?
- Which problem are you experiencing?

If possible, a test highlighting the bug would be great.
If you are fixing a bug, create a pull request, linking the issue with bug's details (if there is any) and provide tests.
The build must be green for the PR being merged.

## Suggesting Enhancements

If you want to propose an enhancements open an issue explaining why you think it would be useful.
Once you get a green light implement stuff, create a PR. Remember to provide tests.
The build must be green for the PR being merged.

## How to develop arkitect

### Requirements

- PHP `^8.0`
- Composer

### Getting started

Install dependencies:

```shell
composer install
```

Some common tasks are available in the Makefile (run `make` without arguments for help).

You can run the full build (code style fix, static analysis, and tests) with:

```shell
make build
```

Or run individual tasks:

```shell
make test      # run tests
make csfix     # run code style fixer
make psalm     # run static analysis
```

### Using Docker instead

If you prefer not to install PHP locally, you can use the provided Dockerfile.

Build the image and enter the container shell:

```shell
make dbi
make shell
```

Or manually:

```shell
docker image build -t phparkitect .
docker run --rm -it --entrypoint= -v $(PWD):/arkitect phparkitect bash
```

Once inside the container, install dependencies with `composer install` and then use the same `make` commands described above.
