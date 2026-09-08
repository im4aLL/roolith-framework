# Ticket B7 - Make Validator fail loudly on unknown rules and missing fields

Status: Done

Order: 21 of 67

Category: Reliability

Severity: High

Source: AUDIT.md item B7

## Problem

`app/Core/Validator.php:54-69` silently skips rule names without matching `Rules` method, so typos pass validation.

## Location

`app/Core/Validator.php:49-73`

## Suggested fix

Throw `InvalidArgumentException` for unknown rule, collect field-missing as failure when `required` present, document behavior.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Verified: Validator::check() throws InvalidArgumentException for unknown rules and records missing required as failure (documented). tests/ValidatorTest.php asserts throw plus missing-required fails; composer test 126 OK.

Phase 2 fix: Validator now supports optional fields - missing keys skip all non-presence rules and only evaluate required/requiredArray/requiredIf (Validator::PRESENCE_RULES), so a missing optional email/url/numeric passes while a present invalid value still fails; ValidatorRules::notExists/exists now typed string|object with full PHPDoc and Rules::exists/notExists throw InvalidArgumentException with chain plus struct validation. tests/ValidatorTest.php updated plus testMissingOptionalFieldPasses, testPresentInvalidOptionalFieldFails, testMissingFieldWithRequiredIfRespectsCondition; tests/RulesHardeningTest.php asserts throw with previous; composer test 142 OK.
