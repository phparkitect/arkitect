# Fix: `appliesTo()` incorrectly excludes mutually exclusive class states

## Summary

Fixes #560 - `IsNotAbstract` (and other similar expressions) incorrectly excluded final classes when used in `that()` clauses, preventing subsequent rules from being evaluated.

## Problem Description

### The Bug

When using `IsNotAbstract` as a filter in `that()`, final classes were incorrectly excluded:

```php
Rule::allClasses()
    ->that(new IsNotAbstract())  // BUG: Excluded final classes!
    ->should(new HaveNameMatching('*Test'))
    ->because('all tests must follow naming convention');
```

**Result**: Final test classes with wrong names were NOT flagged as violations.

### Root Cause

The `appliesTo()` method was confusing two different concepts:

1. ✅ **Type incompatibility**: Interface/Trait/Enum don't have the concept of abstract/final → correctly excluded
2. ❌ **Mutually exclusive states**: Final ↔ Abstract are opposites → incorrectly excluded

**Buggy implementation** (from PR #478):
```php
class IsNotAbstract {
    public function appliesTo(ClassDescription $theClass): bool {
        return !($theClass->isInterface() || $theClass->isTrait() || 
                 $theClass->isEnum() || $theClass->isFinal());  // ← BUG!
    }
}
```

**The logic error**: 
- Final classes **ARE** non-abstract by definition
- Excluding them from `IsNotAbstract` is semantically wrong
- Same bug existed in `IsFinal`, `IsNotFinal`, `IsAbstract`

### Historical Context

- **PR #454**: Introduced `appliesTo()` to avoid false positives on incompatible types
  - Discussion: @fain182 warned that excluding final classes "would not be the expected behaviour from our users"
- **PR #478**: Implemented `appliesTo()` but incorrectly extended it to mutually exclusive states
- **PR #560**: Bug discovered 9 months later by real-world use case

## Solution

### Changed Semantics

**New `appliesTo()` semantics:**
> "Does it make sense to verify this property for this type of class?"

**Rules:**
- ✅ Return `true` for regular classes, final classes, abstract classes
- ❌ Return `false` ONLY for Interface/Trait/Enum (where abstract/final concepts don't apply)
- ✅ Do NOT exclude mutually exclusive states (abstract ↔ final)

### Code Changes

```php
// Before (WRONG)
class IsNotAbstract {
    public function appliesTo(ClassDescription $theClass): bool {
        return !($theClass->isInterface() || $theClass->isTrait() || 
                 $theClass->isEnum() || $theClass->isFinal());
    }
}

// After (CORRECT)
class IsNotAbstract {
    public function appliesTo(ClassDescription $theClass): bool {
        return !($theClass->isInterface() || $theClass->isTrait() || 
                 $theClass->isEnum());
        // Final classes removed - they ARE non-abstract!
    }
}
```

**Same fix applied to:**
- `IsAbstract` (removed `|| $theClass->isFinal()`)
- `IsNotAbstract` (removed `|| $theClass->isFinal()`)
- `IsFinal` (removed `|| $theClass->isAbstract()`)
- `IsNotFinal` (removed `|| $theClass->isAbstract()`)

## Why This Is Correct

### PHP Semantics

In PHP, abstract and final are **mutually exclusive**:
- `final class Foo {}` → `isAbstract()` always returns `false`
- `abstract class Bar {}` → `isFinal()` always returns `false`

### Rule Evaluation Flow

**In `that()` clauses (filtering):**
```php
if (!$spec->appliesTo($classDescription)) {
    return false;  // Skip entire rule chain
}
```

**In `should()` clauses (validation):**
```php
$expression->evaluate($classDescription, $violations, $because);
// appliesTo() is never called here
```

### Expected Behavior Table

| Class Type | `IsAbstract::appliesTo()` | `IsNotAbstract::appliesTo()` | Why |
|------------|---------------------------|------------------------------|-----|
| Regular class | `true` | `true` | Can check abstract property |
| Final class | `true` ✅ | `true` ✅ | Can check abstract property (always false) |
| Abstract class | `true` ✅ | `true` ✅ | Can check abstract property (always true) |
| Interface | `false` | `false` | Abstract concept doesn't apply |
| Trait | `false` | `false` | Abstract concept doesn't apply |
| Enum | `false` | `false` | Abstract concept doesn't apply |

## Tests Added

### Integration Tests

1. **`test_is_not_abstract_in_that_should_include_final_classes()`**
   - Replicates exact scenario from issue #560
   - Verifies final classes are NOT excluded from `IsNotAbstract` filter
   - **Before fix**: ❌ 1/2 violations found
   - **After fix**: ✅ 2/2 violations found

2. **`test_is_not_abstract_in_should_validates_final_classes_correctly()`**
   - Verifies `IsNotAbstract` works correctly in `should()` clauses
   - Tests all class types: abstract, final, regular, interface, trait, enum
   - Confirms only abstract classes generate violations

### Unit Tests

Enhanced existing tests to verify **both** `appliesTo()` and `evaluate()`:
- `test_final_classes_can_be_checked_for_abstract()` - IsAbstract with final
- `test_abstract_classes_can_be_checked_for_final()` - IsFinal with abstract

## Test Results

✅ **All 350 tests pass** (596 assertions)

**Key test outcomes:**
- Integration test that was failing now passes
- No regressions introduced
- Behavior now matches user expectations

## Breaking Changes

**None for end users** - this fixes broken behavior to match expectations.

**For internal tests only**: Tests that codified the bug were updated to reflect correct behavior.

## Migration Guide

No migration needed. Rules that were broken will now work correctly:

```php
// This now works as expected!
Rule::allClasses()
    ->that(new IsNotAbstract())  // ✅ Includes final classes
    ->should(new HaveNameMatching('*Test'))
    ->because('all tests must follow naming convention');
```

## Related Issues/PRs

- Fixes #560
- Related to PR #454 (introduced `appliesTo()`)
- Related to PR #478 (implemented with bug)

## Checklist

- [x] Tests added for the fix
- [x] Tests added for behavior verification
- [x] All existing tests pass
- [x] Documentation (this PR description)
- [x] No breaking changes for users
