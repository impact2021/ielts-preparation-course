# Reading Tests Difficulty - Action Plan

**Date:** 2026-01-17  
**Issue:** Concern about reading tests being too difficult for IELTS  
**Status:** Analysis Complete - Actions Required

---

## Quick Summary

✅ **Good News:** 62% of tests (13/21) are at ideal IELTS difficulty  
⚠️ **Concern:** 2 tests are too difficult (Tests 17, 20)  
📋 **Action Required:** Review and address problematic tests

---

## Tests Requiring Immediate Attention

### 🔴 Test 20 - TOO DIFFICULT
**Problem:** Requires specialized knowledge in marine chemistry and maritime history

**Specific Issues:**
- Ocean Acidification passage: 20+ chemistry/marine biology terms (carbonate ions, pteropods, aragonite, calcification)
- Ancient Navigation passage: 25+ specialized terms across physics, oceanography, astronomy, and history
- Assumes prior subject-specific knowledge

**Recommended Actions:**
1. **Option A (Recommended):** Mark as "Advanced/Specialized - Not for Standard IELTS Practice"
2. **Option B:** Create simplified version with more accessible vocabulary
3. **Option C:** Remove from student access

**Priority:** HIGH

---

### 🔴 Test 17 - TOO DIFFICULT + STRUCTURAL ERRORS
**Problem:** Excessive vocabulary complexity AND broken question links

**Specific Issues:**
- 15 questions NOT linked to reading passages (Q6-12, Q20-23, Q33-36) ← CRITICAL BUG
- Urban Agriculture passage: excessive technical jargon (aquaponics, aeroponics, multispectral cameras)
- Psychology of Color passage: specialized terminology

**Recommended Actions:**
1. **Fix structural errors FIRST** (15 questions not linked)
2. **Then choose:**
   - Option A: Simplify Urban Agriculture passage
   - Option B: Mark as "Advanced Level"
   - Option C: Replace with more accessible content

**Priority:** HIGH (structural errors make test invalid)

---

### ⚠️ Test 13 - BORDERLINE
**Problem:** Technical content at upper IELTS boundary

**Specific Issues:**
- Clock history passage: requires physics background (pendulum mechanics, quartz oscillation)
- Holiday depression passage: medical/psychological terminology

**Recommended Actions:**
1. Review for potential simplification
2. Add contextual scaffolding OR
3. Mark as "Challenging/Advanced"

**Priority:** MEDIUM

---

## Tests at Appropriate Difficulty ✓

### Perfect for Standard IELTS Practice:
- Tests 01, 02, 04, 05, 06, 08, 09, 11, 12, 14, 15, 19, 21 (13 tests)

### Good for Advanced Students:
- Tests 03, 10, 16, 18 (4 tests)

---

## Implementation Plan

### Phase 1: Immediate Fixes (Week 1)
- [ ] Fix Test 17 structural errors (15 unlinked questions)
- [ ] Mark Test 20 as "Advanced/Specialized" in system
- [ ] Mark Test 17 as "Advanced" in system
- [ ] Update documentation to warn about difficulty

### Phase 2: Content Review (Week 2-3)
- [ ] Review Test 20 passages for potential simplification
- [ ] Review Test 17 Urban Agriculture passage
- [ ] Review Test 13 for contextual improvements
- [ ] Get feedback from IELTS teachers on difficulty assessment

### Phase 3: Long-term Improvements (Month 1-2)
- [ ] Create difficulty tier system (Foundation, Standard, Advanced, Specialized)
- [ ] Add difficulty_level metadata to all tests
- [ ] Add recommended_band_score field
- [ ] Create student-facing difficulty indicators
- [ ] Develop vocabulary complexity guidelines for future tests

---

## Difficulty Tier Proposal

### Tier 1: Foundation (IELTS 5.0-6.5)
**Currently:** None identified (consider creating)
- Basic academic vocabulary
- Clear, straightforward topics
- Minimal specialized terminology

### Tier 2: Standard (IELTS 6.5-7.5) ✓
**Currently:** Tests 01, 02, 04, 05, 06, 08, 09, 11, 12, 14, 15, 19, 21
- Academic vocabulary
- General interest topics
- Limited specialized terms, explained in context

### Tier 3: Advanced (IELTS 8.0-8.5)
**Currently:** Tests 03, 10, 16, 18 (+ Test 13 after review)
- Complex academic vocabulary
- Multi-disciplinary topics
- Some specialized terminology

### Tier 4: Specialized (IELTS 9.0 / Subject Experts)
**Currently:** Tests 17, 20 (after marking)
- Highly specialized vocabulary
- Requires domain-specific knowledge
- For advanced learners or subject specialists

---

## Vocabulary Guidelines (Proposed)

### Standard IELTS Tests Should:
✅ Use academic vocabulary accessible to educated readers  
✅ Explain technical terms in context  
✅ Limit specialized terminology to 10-15 terms per passage  
✅ Cover topics of general academic interest  
✅ Require reading comprehension skills, not subject expertise  

### Standard IELTS Tests Should NOT:
❌ Require prior knowledge of chemistry, physics, or advanced science  
❌ Use 20+ specialized terms in a single passage  
❌ Assume familiarity with technical processes without explanation  
❌ Require cross-disciplinary expertise (e.g., physics + oceanography + history)  

---

## Student-Facing Recommendations

### For Test Selection Interface:
Consider adding difficulty indicators:

**Test 05** ⭐⭐⭐ Standard  
*Topics: Fitness, Technology, Astronomy*  
*Recommended for: IELTS 6.5-7.5*

**Test 20** ⭐⭐⭐⭐⭐ Specialized  
*Topics: Marine Chemistry, Technology Policy, Maritime History*  
*Recommended for: Advanced learners (8.5+) or those with science background*  
*⚠️ Note: Contains specialized scientific vocabulary*

---

## Quality vs. Difficulty Matrix

| Test | Difficulty | Quality Issues | Action Needed |
|:----:|:----------:|:--------------|:--------------|
| 01 | Moderate | Grammar (1) | Minor fix |
| 02 | Moderate | None | ✓ Good |
| 03 | Challenging | Missing feedback (20) | Fix feedback |
| 04 | Moderate | None | ✓ Good |
| 05 | Moderate | Missing feedback (4) | Fix feedback |
| 06 | Moderate-High | Missing feedback (17) | Fix feedback |
| 07 | Moderate | Grammar (1) | Minor fix |
| 08 | Moderate | None | ✓ Good |
| 09 | Moderate | None | ✓ Good |
| 10 | Moderate-High | None | ✓ Good |
| 11 | Moderate | None | ✓ Good |
| 12 | Moderate | None | ✓ Good |
| 13 | **Challenging** | Missing feedback (7) | **Review difficulty** + Fix feedback |
| 14 | Moderate | None | ✓ Good |
| 15 | Moderate | None | ✓ Good |
| 16 | Moderate-High | None | ✓ Good |
| **17** | **Too Difficult** | **Linking errors (15)** | **🔴 Critical fixes** |
| 18 | Challenging | None | ✓ Good |
| 19 | Moderate | None | ✓ Good |
| **20** | **Too Difficult** | None | **🔴 Mark as specialized** |
| 21 | Moderate | None | ✓ Good |

---

## Success Metrics

### How to measure improvement:
- [ ] Student feedback on test difficulty
- [ ] Completion rates by test
- [ ] Score distributions (check for floor/ceiling effects)
- [ ] Time-to-complete analysis
- [ ] Student self-reported IELTS band scores vs. test performance

### Expected Outcomes After Implementation:
- Students can select appropriate difficulty level
- Reduced frustration with overly difficult content
- Better alignment with official IELTS standards
- Clear progression path from standard to advanced

---

## Technical Implementation Notes

### To Add Difficulty Metadata to JSON:

```json
{
  "title": "Academic IELTS Reading Test 20",
  "difficulty_level": "specialized",
  "recommended_band_score": "8.5-9.0",
  "topic_areas": ["marine_science", "technology_policy", "maritime_history"],
  "vocabulary_complexity": "very_high",
  "requires_specialized_knowledge": true,
  "warning_message": "This test contains specialized scientific vocabulary and may be challenging for students without a science background."
}
```

### To Fix Test 17 Linking Errors:

Check questions Q6-12, Q20-23, Q33-36 and ensure `reading_text_id` is set correctly:
- `reading_text_id: 0` for Passage 1
- `reading_text_id: 1` for Passage 2  
- `reading_text_id: 2` for Passage 3

Currently these questions have `reading_text_id: null` or invalid values.

---

## Next Steps

1. **Review this analysis** with IELTS teaching team
2. **Prioritize actions:**
   - Fix Test 17 structural errors (highest priority)
   - Mark Tests 17 and 20 as advanced/specialized
   - Review Test 13
3. **Plan content updates** for problematic tests
4. **Implement difficulty tier system** for student selection
5. **Add metadata** to enable filtering by difficulty
6. **Monitor student feedback** after changes

---

## Conclusion

**The concern about test difficulty is valid.** Two tests (17 and 20) exceed appropriate IELTS standards due to excessive specialized vocabulary. However, the majority of tests (62%) are well-calibrated for standard IELTS preparation.

**Recommended approach:**
1. Fix Test 17 structural errors immediately
2. Mark Tests 17 and 20 as "Advanced/Specialized" 
3. Implement difficulty tier system
4. Continue using Tests 01-16, 18-19, 21 for standard IELTS practice

This will ensure students have access to appropriate difficulty levels while maintaining challenging content for advanced learners.

---

**For full analysis details, see:** [READING_TESTS_DIFFICULTY_ANALYSIS.md](READING_TESTS_DIFFICULTY_ANALYSIS.md)
