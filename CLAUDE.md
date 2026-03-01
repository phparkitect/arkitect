# Claude Configuration for PHPArchitect

PHPArchitect is an open-source static analysis tool for enforcing architectural constraints in PHP applications. It uses the `nikic/php-parser` library to parse PHP code and validate it against defined architectural rules.

## Project Overview

- **Language:** PHP 8.0+ (minimum requirement from `composer.json`)
- **Type Checking:** Psalm
- **Testing:** PHPUnit
- **Code Style:** PHP-CS-Fixer
- **Key Dependencies:**
  - `nikic/php-parser` (~5) - PHP AST parsing
  - `symfony/console` - CLI interface
  - `phpstan/phpdoc-parser` - Doc parsing
  - `symfony/event-dispatcher` - Event system
  - `symfony/finder` - File discovery

## Quick Start for Common Tasks

**Adding a new rule?**
→ `src/Expression/ForClasses/` + tests + update `README.md`

**Fixing a bug?**
→ Find relevant code, write failing test, fix, verify with `make test`

**Changed user-facing behavior?**
→ Update `README.md` (installation/usage/rules sections)

**Ready to commit?**
→ `make test && make csfix && make psalm` (all must pass)

**Need examples?**
→ Look at existing code in `src/Expression/ForClasses/` and tests in `tests/`

## Development Workflow

1. **Branching:** Create feature branches for new work
2. **Testing:** All changes require passing unit tests
3. **Code Style:** Run `make csfix` to fix code style
4. **Type Safety:** Ensure no Psalm errors with `make psalm`
5. **Pull Requests:** Submit PR for review before merging to main

## Common Workflows

### Adding a New Rule/Constraint

1. **Create the constraint class** in `src/Expression/ForClasses/`
   - Extend appropriate base class or implement interface
   - Implement the constraint logic
   - Add proper type hints (PHP 7.4+ compatible)

2. **Write unit tests** in `tests/Unit/Expression/ForClasses/`
   - Test success cases
   - Test failure cases with proper violation messages
   - Test edge cases

3. **Update README.md**
   - Add rule to "Available rules" section
   - Provide clear usage example
   - Explain the rule's purpose and use cases

4. **Run validation**
   ```bash
   make test           # Ensure tests pass
   make csfix          # Fix code style
   make psalm          # Check types
   ```

### Fixing a Bug/Issue

1. **Understand the issue**
   - Read the issue description carefully
   - Check existing tests in `tests/` for related functionality
   - Use Grep/Read to find relevant code

2. **Write a failing test** (TDD approach)
   - Create or update test in appropriate location
   - Verify the test fails (reproduces the bug)

3. **Fix the code**
   - Make minimal changes to fix the issue
   - Avoid refactoring unrelated code
   - Keep PHP 7.4 compatibility in mind

4. **Verify the fix**
   ```bash
   make test           # All tests should pass
   make csfix          # Fix any style issues
   make psalm          # Ensure no type errors
   ```

5. **Update documentation if needed**
   - Update README.md if behavior changed
   - Add comments only if logic is non-obvious

### Updating Documentation

**When to update:**
- Adding/modifying rules → Update `README.md` "Available rules" section
- Changing public API → Update `README.md` usage examples
- Fixing bugs that affect user behavior → Update relevant docs

**Where to find what:**
- **User documentation:** `README.md` (installation, usage, rules)
- **Contributor guide:** `CONTRIBUTING.md`
- **Code examples:** Look at existing rules in `src/Expression/ForClasses/`
- **Test examples:** Check `tests/` for patterns and conventions

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

### Source Code (`src/`)
```
src/
  ├── Analyzer/          # Constraint analysis engine
  ├── CLI/               # Command-line interface
  │   ├── Command/       # CLI commands (check, etc.)
  │   ├── Printer/       # Output formatters
  │   └── Progress/      # Progress indicators
  ├── Exceptions/        # Custom exceptions
  ├── Expression/        # Rule expressions (constraints)
  │   └── ForClasses/    # Class-level constraints
  ├── PHPUnit/           # PHPUnit integration
  ├── RuleBuilders/      # Fluent rule builders
  │   └── Architecture/  # Architectural pattern builders
  └── Rules/             # Core rule engine
      └── DSL/           # Domain-specific language parsing
tests/
  └── [mirrors src/ structure]
bin/
  ├── phparkitect        # Main executable
  └── [other tools]
```

### Documentation Files
- **`README.md`** - User-facing documentation, installation, usage, available rules
- **`CONTRIBUTING.md`** - How to contribute (code, tests, docs)
- **`CONTRIBUTORS.md`** - List of project contributors
- **`CLAUDE.md`** - This file (Claude AI context)

### Configuration Files
- **`phparkitect.php`** - Example configuration file for users
- **`phparkitect-stub.php`** - PHAR entry point
- **`composer.json`** - Dependencies and project metadata
- **`box.json`** - PHAR build configuration
- **`Makefile`** - Development commands

## Critical Areas

### 1. Parser and AST
- **Location:** `src/Parser/`
- **Purpose:** Parses PHP source code into an Abstract Syntax Tree
- **Dependencies:** Uses `nikic/php-parser` (~5) for parsing
- **Caution:** Parser edge cases can cause failures on unusual syntax. Be careful with:
  - First-class callables (PHP 8.1+)
  - Union/intersection types
  - Readonly properties
  - Dynamic property/method access
- **When working here:** Always test with various PHP versions (7.4, 8.0+)

### 2. Constraint Checking (Rules & Expressions)
- **Locations:**
  - `src/Rules/` - Core rule engine and DSL
  - `src/Expression/ForClasses/` - Individual constraint implementations
  - `src/Analyzer/` - Analysis orchestration
- **Purpose:** Validates code against defined architectural constraints
- **Critical:** This is the core logic of the tool
- **Documentation link:** Rules are documented in `README.md` "Available rules" section
- **When adding a rule:**
  1. Add constraint class in `src/Expression/ForClasses/`
  2. Add tests in `tests/Unit/Expression/ForClasses/`
  3. Document in `README.md`

### 3. Rule Builders (Fluent API)
- **Location:** `src/RuleBuilders/`, `src/Rules/RuleBuilder.php`
- **Purpose:** Provides fluent interface for users to define rules
- **Example:** `Rule::allClasses()->that(...)->should(...)->because(...)`
- **Documentation:** Examples in `README.md` Quick Start section
- **When modifying:** Update README examples to reflect API changes

### 4. CLI Interface
- **Location:** `src/CLI/`
- **Purpose:** Command-line interface, output formatting, progress tracking
- **Main command:** `src/CLI/Command/Check.php`
- **User impact:** Changes here affect user experience directly

### 5. Performance
- **Concern:** Must handle analysis of large codebases efficiently
- **Watch:** Avoid O(n²) algorithms on file collections, cache parsing results where possible
- **Test:** Use large codebases to verify performance doesn't degrade

## Code Style and Conventions

- **PSR-4 Autoloading:** Namespace `Arkitect\` maps to `src/`
- **Test Namespace:** `Arkitect\Tests\` maps to `tests/`
- **Naming:** Use descriptive names for classes and methods
- **Type Hints:** Use proper type declarations (PHP 8.0+ compatible)
- **Comments:** Only add when logic is non-obvious; avoid redundant docblocks

## PHP Version Compatibility

This project supports **PHP 8.0+**. The CI matrix runs PHP 8.0 through 8.4.

- **Enum fixtures** require PHP 8.1+ — mark tests that load enum files with
  `@requires PHP 8.1` to avoid `ParseError` on PHP 8.0.

## What to Avoid

1. **Over-engineering:** Keep solutions simple and focused on the requirement
2. **Unnecessary comments/docstrings:** Only document complex logic
3. **Feature flags/backwards-compatibility shims:** Modify code directly when possible
4. **Parsing edge cases:** When working with parser logic, test against:
   - PHP 8.0 syntax (minimum supported)
   - PHP 8.1+ features (enums, fibers — require `@requires PHP 8.1` in tests)
   - Mixed scenarios that could break
5. **Test coverage:** Always add unit tests for new functionality

## Reflection-based expression rules — mandatory autoloading

All `src/Expression/ForClasses/` expressions (`Implement`, `NotImplement`, `Extend`,
`NotExtend`, `HaveTrait`, `NotHaveTrait`) use **pure reflection** with no static fallback
and **no silent skip**: if a class cannot be reflected, `ReflectionException` propagates.

```php
$reflection = new \ReflectionClass($theClass->getFQCN()); // throws on missing class
```

Why reflection and not the AST parse tree:
- Transitive inheritance — `getInterfaceNames()` / `getParentClass()` / `getTraitNames()`
  walk the full hierarchy, the static parser only sees what is written in the file.
- **No `catch → return`**: a `ReflectionException` means autoloading is misconfigured;
  silently skipping it would produce false negatives (rules apparently passing when they
  were never actually evaluated). The exception surfaces the problem immediately.

`ClassDescription::getFQCN()` is annotated `@return class-string` (Psalm pseudo-type,
not a PHP type) so that Psalm does not flag the `ReflectionClass` constructor calls.

**Consequence:** every class evaluated by these expressions must be autoloadable.
No vfsStream, no inline PHP strings with fake namespaces in tests.

### What about `@throws` docblock tags?

`@throws` is handled at the **dependency-collection** level (AST, not reflection).
It adds the explicitly declared exception FQCN as a dependency of the class — a direct,
non-transitive relationship. Reflection is not needed here: the parser already resolves
FQCNs via `use` statements. If you want to verify that thrown exceptions extend a base
class, write a separate rule using `Extend`, which now works transitively.

### Test fixture conventions

- Real fixture files live in `tests/*/Fixtures/` subdirectories
- PSR-4 namespaced fixtures → covered by `"Arkitect\\Tests\\": "tests/"` in autoload-dev
- Global-namespace traits (e.g. `DatabaseTransactions`) → listed in `classmap` in `composer.json`
- E2E mvc fixtures (`App\Controller\*`, etc.) → `"App\\": "tests/E2E/_fixtures/mvc/"`
- `ContainerAwareInterface` (global namespace) → listed in `classmap`
- After adding fixtures: run `composer dump-autoload`

### composer.json autoload-dev (current state)

```json
"autoload-dev": {
    "psr-4": {
        "Arkitect\\Tests\\": "tests/",
        "App\\": "tests/E2E/_fixtures/mvc/"
    },
    "classmap": [
        "tests/E2E/_fixtures/mvc/ContainerAwareInterface.php",
        "tests/Integration/PHPUnit/Fixtures/DatabaseTransactions.php",
        "tests/Integration/PHPUnit/Fixtures/RefreshDatabase.php",
        "tests/Integration/PHPUnit/Fixtures/HasUuid.php"
    ]
}
```

### phpunit.xml — fixture directories excluded from test discovery

```xml
<exclude>tests/Integration/PHPUnit/Fixtures</exclude>
<exclude>tests/Integration/Fixtures</exclude>
<exclude>tests/Unit/Analyzer/FileParser/Fixtures</exclude>
<exclude>tests/Unit/Rules/Fixtures</exclude>
```

## Testing Guidelines

- Unit tests go in `tests/` mirroring the `src/` structure
- Use PHPUnit (9.6+, 10.0+, 11.0+)
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

### Configuration & Build
- **`composer.json`** - Dependencies, autoloading, PHP version requirements
- **`Makefile`** - All development commands (test, csfix, psalm, build, phar)
- **`box.json`** - PHAR build configuration
- **`phparkitect-stub.php`** - PHAR entry point
- **`.php-cs-fixer.php`** - Code style rules

### Documentation (IMPORTANT)
- **`README.md`** - **PRIMARY USER DOCUMENTATION**
  - Installation instructions
  - Usage examples
  - **Available rules section** (must update when adding rules!)
  - Configuration examples
- **`CONTRIBUTING.md`** - How to contribute, development setup
- **`CONTRIBUTORS.md`** - Project contributors list

### Entry Points
- **`bin/phparkitect`** - Main CLI executable
- **`src/CLI/Command/Check.php`** - Main check command implementation

### Core Classes to Know
- **`src/Rules/Rule.php`** - Base rule class
- **`src/Rules/RuleBuilder.php`** - Fluent API entry point
- **`src/Expression/ForClasses/`** - All constraint implementations
- **`src/Analyzer/`** - Analysis engine

### Testing
- **`bin/phpunit`** - Test runner
- **`tests/`** - Test files (mirror `src/` structure)
- **`phpunit.xml.dist`** - PHPUnit configuration

## Quick Reference: Code ↔ Documentation Links

| Task | Code Location | Documentation | Tests |
|------|---------------|---------------|-------|
| Add new rule/constraint | `src/Expression/ForClasses/` | `README.md` "Available rules" | `tests/Unit/Expression/ForClasses/` |
| Modify rule builder API | `src/Rules/RuleBuilder.php` | `README.md` examples | `tests/Unit/Rules/` |
| Change CLI behavior | `src/CLI/Command/` | `README.md` "Usage" | `tests/Unit/CLI/` |
| Fix parser issues | `src/Parser/` | N/A | `tests/Unit/Parser/` |
| Update analysis logic | `src/Analyzer/` | N/A | `tests/Unit/Analyzer/` |

## Pre-Commit Checklist

Before committing changes, ensure:

- [ ] **Tests pass:** `make test`
- [ ] **Code style fixed:** `make csfix`
- [ ] **No type errors:** `make psalm`
- [ ] **PHP 8.0 compatible:** CI runs on PHP 8.0–8.4; enum fixtures need `@requires PHP 8.1`
- [ ] **Documentation updated:** If adding/changing rules, update `README.md`
- [ ] **Tests added:** New functionality has corresponding tests

## Quick Troubleshooting

**Tests failing?**
- Run `make test` to see failures
- Check PHP version compatibility (PHP 7.4 vs 8.0+)
- Ensure you didn't use PHP 8.0+ syntax

**Psalm errors?**
- Run `make psalm` to see type issues
- Check type hints and return types
- Use PHPDoc for complex types

**Code style issues?**
- Run `make csfix` to auto-fix
- Check for trailing commas in function parameters (PHP 7.4 incompatible)

**Can't find where to add a rule?**
- Look at existing rules in `src/Expression/ForClasses/`
- Check tests in `tests/Unit/Expression/ForClasses/` for patterns
- Follow the "Adding a New Rule" workflow above

## When to Ask for Help

- Architecture questions about parsing or analysis flow
- Performance optimization strategies
- Complex test scenarios
- Integration with Symfony components
- Uncertainty about where to implement a feature

---

*Last updated: February 2026*
