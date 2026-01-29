# IELTS Core and Plus Tiers - Visual Examples

## 🎨 Pricing Page Layouts

### Example 1: Side-by-Side Comparison

```
┌─────────────────────────────────────────────────────────────────────┐
│                        CHOOSE YOUR PLAN                              │
└─────────────────────────────────────────────────────────────────────┘

┌──────────────────────────┐  ┌──────────────────────────┐
│    IELTS CORE            │  │    IELTS PLUS ⭐         │
│    Academic Module       │  │    Academic Module       │
│                          │  │                          │
│        $39.99            │  │        $59.95            │
│       /30 days           │  │       /90 days           │
│                          │  │                          │
│  ✓ Full course access    │  │  ✓ Full course access    │
│  ✓ Practice tests        │  │  ✓ Practice tests        │
│  ✓ Reading materials     │  │  ✓ Reading materials     │
│  ✓ Listening exercises   │  │  ✓ Listening exercises   │
│  ✓ Writing tasks         │  │  ✓ Writing tasks         │
│                          │  │  ✓ 2 LIVE SPEAKING       │
│                          │  │    ASSESSMENTS 🎯        │
│                          │  │  ✓ Extended 90-day       │
│                          │  │    access                │
│                          │  │                          │
│  [   Get Started   ]     │  │  [   Get Started   ]     │
└──────────────────────────┘  └──────────────────────────┘
```

**HTML Implementation:**
```html
<div class="pricing-grid">
    <div class="pricing-card core">
        <h3>IELTS Core</h3>
        <p class="subtitle">Academic Module</p>
        <div class="price">
            <span class="amount">[ielts_price type="academic_full"]</span>
            <span class="period">/30 days</span>
        </div>
        <ul class="features">
            <li>✓ Full course access</li>
            <li>✓ Practice tests</li>
            <li>✓ Reading materials</li>
            <li>✓ Listening exercises</li>
            <li>✓ Writing tasks</li>
        </ul>
        <a href="/register?type=academic_full" class="btn">Get Started</a>
    </div>
    
    <div class="pricing-card plus featured">
        <div class="badge">BEST VALUE</div>
        <h3>IELTS Plus ⭐</h3>
        <p class="subtitle">Academic Module</p>
        <div class="price">
            <span class="amount">[ielts_price type="academic_plus"]</span>
            <span class="period">/90 days</span>
        </div>
        <ul class="features">
            <li>✓ Full course access</li>
            <li>✓ Practice tests</li>
            <li>✓ Reading materials</li>
            <li>✓ Listening exercises</li>
            <li>✓ Writing tasks</li>
            <li class="highlight"><strong>✓ 2 LIVE SPEAKING ASSESSMENTS 🎯</strong></li>
            <li class="highlight"><strong>✓ Extended 90-day access</strong></li>
        </ul>
        <a href="/register?type=academic_plus" class="btn btn-primary">Get Started</a>
    </div>
</div>
```

---

### Example 2: Comparison Table

```
┌─────────────────────────────────────────────────────────────────────┐
│                     MEMBERSHIP COMPARISON                            │
├────────────────┬──────────────────┬──────────────────┬──────────────┤
│ Feature        │ Academic Core    │ Academic Plus ⭐ │ General Plus │
├────────────────┼──────────────────┼──────────────────┼──────────────┤
│ Duration       │ 30 days          │ 90 days          │ 90 days      │
│ Price          │ $39.99           │ $59.95           │ $59.95       │
│ Full Access    │ ✓                │ ✓                │ ✓            │
│ Practice Tests │ ✓                │ ✓                │ ✓            │
│ Speaking Tests │ —                │ 2 LIVE SESSIONS  │ 2 LIVE       │
│ Best For       │ Quick prep       │ Thorough prep    │ Thorough     │
├────────────────┼──────────────────┼──────────────────┼──────────────┤
│                │ [Get Started]    │ [Get Started]    │[Get Started] │
└────────────────┴──────────────────┴──────────────────┴──────────────┘
```

**HTML Implementation:**
```html
<table class="comparison-table">
    <thead>
        <tr>
            <th>Feature</th>
            <th>IELTS Core<br><small>Academic</small></th>
            <th class="featured">IELTS Plus ⭐<br><small>Academic</small></th>
            <th>IELTS Plus<br><small>General Training</small></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Duration</td>
            <td>30 days</td>
            <td class="featured"><strong>90 days</strong></td>
            <td><strong>90 days</strong></td>
        </tr>
        <tr>
            <td>Price</td>
            <td>[ielts_price type="academic_full"]</td>
            <td class="featured">[ielts_price type="academic_plus"]</td>
            <td>[ielts_price type="general_plus"]</td>
        </tr>
        <tr>
            <td>Full Course Access</td>
            <td>✓</td>
            <td class="featured">✓</td>
            <td>✓</td>
        </tr>
        <tr>
            <td>Practice Tests</td>
            <td>✓</td>
            <td class="featured">✓</td>
            <td>✓</td>
        </tr>
        <tr class="highlight-row">
            <td>Live Speaking Assessments</td>
            <td>—</td>
            <td class="featured"><strong>2 Sessions 🎯</strong></td>
            <td><strong>2 Sessions 🎯</strong></td>
        </tr>
        <tr>
            <td>Best For</td>
            <td>Quick preparation</td>
            <td class="featured">Comprehensive prep</td>
            <td>Comprehensive prep</td>
        </tr>
        <tr>
            <td></td>
            <td><a href="?type=academic_full" class="btn">Choose</a></td>
            <td class="featured"><a href="?type=academic_plus" class="btn btn-primary">Choose</a></td>
            <td><a href="?type=general_plus" class="btn">Choose</a></td>
        </tr>
    </tbody>
</table>
```

---

### Example 3: Feature Callout Boxes

```
┌─────────────────────────────────────────────────────────────────────┐
│                  WHAT'S INCLUDED IN PLUS?                            │
└─────────────────────────────────────────────────────────────────────┘

┌──────────────────────┐  ┌──────────────────────┐  ┌─────────────────┐
│   🗓️ 90 DAYS         │  │   🎤 2 SPEAKING      │  │   💰 ONLY       │
│                      │  │      ASSESSMENTS     │  │      $59.95     │
│  3 months of full    │  │                      │  │                 │
│  access vs 30 days   │  │  Live 1-on-1         │  │  Just $20 more  │
│  in Core tier        │  │  sessions with       │  │  than Core for  │
│                      │  │  certified IELTS     │  │  3x the time +  │
│  Study at your       │  │  instructors         │  │  speaking help  │
│  own pace with       │  │                      │  │                 │
│  plenty of time      │  │  Real exam format    │  │  Best value for │
│                      │  │  Personalized        │  │  serious        │
│                      │  │  feedback            │  │  students!      │
└──────────────────────┘  └──────────────────────┘  └─────────────────┘
```

---

### Example 4: Simple List with Benefits

```
Choose Your Academic Module Plan:

□ IELTS Core (Academic Module)
  • Price: $39.99
  • Duration: 30 days
  • Full access to all course materials
  • Perfect for focused, short-term preparation
  [Select Core Plan]

☑ IELTS Plus (Academic Module) ⭐ RECOMMENDED
  • Price: $59.95
  • Duration: 90 days (3 months)
  • Full access to all course materials
  • 2 LIVE SPEAKING ASSESSMENTS with certified instructors
  • Personalized feedback and band score estimates
  • Best value for comprehensive IELTS preparation
  [Select Plus Plan]
```

**HTML Implementation:**
```html
<div class="plan-selector">
    <h2>Choose Your Academic Module Plan:</h2>
    
    <div class="plan-option">
        <input type="radio" name="plan" id="core" value="academic_full">
        <label for="core">
            <h3>IELTS Core (Academic Module)</h3>
            <ul>
                <li>Price: [ielts_price type="academic_full"]</li>
                <li>Duration: 30 days</li>
                <li>Full access to all course materials</li>
                <li>Perfect for focused, short-term preparation</li>
            </ul>
            <button class="btn">Select Core Plan</button>
        </label>
    </div>
    
    <div class="plan-option featured">
        <span class="badge">RECOMMENDED</span>
        <input type="radio" name="plan" id="plus" value="academic_plus" checked>
        <label for="plus">
            <h3>IELTS Plus (Academic Module) ⭐</h3>
            <ul>
                <li>Price: [ielts_price type="academic_plus"]</li>
                <li>Duration: 90 days (3 months)</li>
                <li>Full access to all course materials</li>
                <li class="highlight"><strong>2 LIVE SPEAKING ASSESSMENTS with certified instructors</strong></li>
                <li>Personalized feedback and band score estimates</li>
                <li>Best value for comprehensive IELTS preparation</li>
            </ul>
            <button class="btn btn-primary">Select Plus Plan</button>
        </label>
    </div>
</div>
```

---

## 📱 Mobile-Friendly Stacked Layout

```
┌───────────────────────────┐
│   IELTS Core              │
│   Academic Module         │
│                           │
│   $39.99 / 30 days        │
│                           │
│   ✓ Full access           │
│   ✓ All materials         │
│                           │
│   [Select Plan]           │
└───────────────────────────┘

┌───────────────────────────┐
│   IELTS Plus ⭐           │
│   Academic Module         │
│   BEST VALUE              │
│                           │
│   $59.95 / 90 days        │
│                           │
│   ✓ Full access           │
│   ✓ All materials         │
│   ✓ 2 LIVE SPEAKING       │
│     ASSESSMENTS 🎯        │
│   ✓ Extended access       │
│                           │
│   [Select Plan]           │
└───────────────────────────┘
```

---

## 🎯 Key Messaging Points

### For Core Tier:
- "30 days of comprehensive IELTS preparation"
- "Perfect for focused exam preparation"
- "Full access to all course materials"
- "Great value for short-term study"

### For Plus Tier:
- "**90 days** of extended access - **3x longer than Core!**"
- "**Includes 2 live speaking assessments** with certified IELTS instructors"
- "Get personalized feedback on your speaking performance"
- "Practice with real IELTS exam format"
- "Receive estimated band scores"
- "**Best value** for comprehensive preparation"
- "Only $20 more than Core for triple the time plus live assessments"

---

## 💡 Recommended CSS for Visual Distinction

```css
/* Make Plus tier stand out */
.pricing-card.plus {
    border: 3px solid #0073aa;
    box-shadow: 0 4px 12px rgba(0, 115, 170, 0.2);
    position: relative;
}

.pricing-card.plus .badge {
    position: absolute;
    top: -12px;
    right: 20px;
    background: #0073aa;
    color: white;
    padding: 5px 15px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: bold;
}

.features .highlight {
    background: #fff3cd;
    padding: 8px;
    margin: 5px -8px;
    border-left: 4px solid #0073aa;
    font-weight: bold;
}

/* Speaking assessment icon */
.feature-speaking::before {
    content: "🎯 ";
}
```

---

## ✅ Summary

All pricing pages should clearly communicate:

1. **Duration difference:** Core = 30 days, Plus = 90 days
2. **Speaking assessments:** Exclusive to Plus tier, clearly highlighted
3. **Value proposition:** Plus tier is best value for serious students
4. **Pricing:** Use shortcodes to display current prices
5. **Visual distinction:** Plus tier should be visually prominent

The new tier structure makes it easy for students to see the value difference and choose the option that best fits their preparation timeline and budget.
