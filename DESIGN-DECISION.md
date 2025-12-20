# Design Decision: Intentional Code Duplication

## Context

The problem statement explicitly requested:
> "Go back to the question types, delete everything and rebuild from scratch. Make all the question types 100% independent of each other, even though the appearance of many of these question types is going to be similar."

## Code Review Findings

The automated code review identified several instances of code duplication:
1. Option text display logic in templates
2. Scoring logic in quiz handler
3. UI field show/hide logic in admin JavaScript

## Why We Kept the Duplication

### 1. User's Explicit Requirement
The user spent 12 hours debugging issues caused by shared code paths and explicitly requested complete independence, even at the cost of duplication.

### 2. Bug Prevention
The original bug was caused by shared code between question types. By duplicating the code:
- Each type is completely isolated
- Changes to one type cannot affect others
- Debugging is easier (clear which type is executing)
- Future modifications won't introduce cross-contamination

### 3. Maintainability Trade-off
**Option A: DRY (Don't Repeat Yourself) - Shared Code**
- Pros: Less code, single point of change
- Cons: **This caused the original bug**, harder to debug, changes affect multiple types

**Option B: WET (Write Everything Twice) - Duplicated Code**
- Pros: **Complete isolation**, easy debugging, changes are localized
- Cons: More code, multiple points of change

Given the user's 12-hour debugging experience and explicit request, we chose Option B.

### 4. Acceptable Duplication Level
The duplicated code is:
- Simple and straightforward (not complex business logic)
- Well-documented with comments
- Easy to understand and modify
- Unlikely to need frequent changes

## Code Review Comments Response

### Comment: "Consider extracting into a helper function"
**Response:** No. Helper functions would create shared code paths, which is exactly what we're avoiding. The user explicitly requested 100% independence.

### Comment: "Creates maintenance overhead"
**Response:** Yes, intentionally. This is an acceptable trade-off for preventing the bug that cost the user 12 hours of debugging time.

### Comment: "Consider extracting common pattern into a private helper method"
**Response:** No. A shared helper method would couple the question types together, potentially reintroducing the original bug in the future.

## Future Considerations

If a bug needs to be fixed in the duplicated code:
1. The fix needs to be applied to all relevant instances
2. This is intentional and acceptable
3. The benefit of isolation outweighs the maintenance cost
4. Tests should verify all question types independently

## Conclusion

The code duplication is **intentional and by design** to meet the user's explicit requirement for 100% independence between question types. This design choice prioritizes bug prevention and debugging ease over code brevity.

The review comments are valid from a general DRY perspective, but in this specific context, the duplication is the correct solution.
