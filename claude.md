# Claude Configuration for PHPArchitect

PHPArchitect is an open-source static analysis tool for enforcing architectural constraints in PHP applications. It uses the `nikic/php-parser` library to parse PHP code and validate it against defined architectural rules.

## Project Overview

- **Language:** PHP (7.4+, 8.0+)
- **Type Checking:** Psalm
- **Testing:** PHPUnit
- **Code Style:** PHP-CS-Fixer
- **Key Dependencies:**
  - `nikic/php-parser` (~5) - PHP AST parsing
  - `symfony/console` - CLI interface
  - `phpstan/phpdoc-parser` - Doc parsing
  - `symfony/event-dispatcher` - Event system
  - `symfony/finder` - File discovery

## Development Workflow

1. **Branching:** Create feature branches for new work
2. **Testing:** All changes require passing unit tests
3. **Code Style:** Run `make csfix` to fix code style
4. **Type Safety:** Ensure no Psalm errors with `make psalm`
5. **Pull Requests:** Submit PR for review before merging to main

## Common Commands

```bash
make test              # Run all unit tests
make test_<name>       # Run specific test (e.g., make test_Parser)
make build             # Full build: install deps, fix code style, run psalm, run tests
make csfix             # Fix code style issues
make psalm             # Run type checking
make coverage          # Generate coverage report
make phar              # Build PHAR executable
```

## Project Structure

```
src/
  ├── Contracts/         # Core interfaces and contracts
  ├── Parser/            # AST parsing logic
  ├── Analyzer/          # Constraint analysis engine
  ├── Rules/             # Architectural rule definitions
  └── [other modules]
tests/
  └── [test files matching src/ structure]
bin/                      # Executable files
```

## Critical Areas

### 1. Parser and AST
- **Location:** `src/Parser/`
- **Purpose:** Parses PHP source code into an Abstract Syntax Tree
- **Caution:** Parser edge cases can cause failures on unusual syntax. Be careful with:
  - First-class callables (PHP 8.1+)
  - Union/intersection types
  - Readonly properties
  - Dynamic property/method access

### 2. Constraint Checking
- **Location:** `src/Analyzer/`, `src/Rules/`
- **Purpose:** Validates code against defined architectural constraints
- **Critical:** This is the core logic of the tool

### 3. Performance
- **Concern:** Must handle analysis of large codebases efficiently
- **Watch:** Avoid O(n²) algorithms on file collections, cache parsing results where possible

## Code Style and Conventions

- **PSR-4 Autoloading:** Namespace `Arkitect\` maps to `src/`
- **Test Namespace:** `Arkitect\Tests\` maps to `tests/`
- **Naming:** Use descriptive names for classes and methods
- **Type Hints:** Use proper type declarations (PHP 7.4+ compatible)
- **Comments:** Only add when logic is non-obvious; avoid redundant docblocks

## What to Avoid

1. **Over-engineering:** Keep solutions simple and focused on the requirement
2. **Unnecessary comments/docstrings:** Only document complex logic
3. **Feature flags/backwards-compatibility shims:** Modify code directly when possible
4. **Parsing edge cases:** When working with parser logic, test against:
   - PHP 7.4 syntax (older code)
   - PHP 8.0+ features (newer syntax)
   - Mixed scenarios that could break
5. **Test coverage:** Always add unit tests for new functionality

## Testing Guidelines

- Unit tests go in `tests/` mirroring the `src/` structure
- Use PHPUnit (7.5+, 9.0+, or 10.0+)
- Use prophecy for mocking
- Aim for good coverage of critical paths
- Test both success and edge cases

## Building and Deployment

### Local Build
```bash
make build
```

### Docker
```bash
make dbi                # Build Docker image
docker run --rm -it --entrypoint= -v $(PWD):/arkitect phparkitect bash
```

### PHAR Distribution
```bash
make phar              # Creates phparkitect.phar
```

## Key Files to Understand

- `composer.json` - Project metadata and dependencies
- `Makefile` - Development commands
- `bin/phpunit` - Test runner
- `bin/php-cs-fixer` - Code style fixer
- `box.json` - PHAR configuration
- `phparkitect-stub.php` - PHAR entry point

## When to Ask for Help

- Architecture questions about parsing or analysis flow
- Performance optimization strategies
- Complex test scenarios
- Integration with Symfony components

---

*Last updated: 2025*
