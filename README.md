# IELTS Preparation Course Plugin

WordPress plugin for managing IELTS preparation exercises, quizzes, and tests.

## 📚 Documentation

### Quick References
- **[Dropdown Question FAQ](non-plugin-files/root-docs/DROPDOWN-QUESTION-FAQ.md)** - Where to put dropdown options and examples
- **[JSON Format Guide](TEMPLATES/JSON-FORMAT-README.md)** - Complete JSON import format reference
- **[Migration Guide v10](old-md-files/MIGRATION_GUIDE_V10.md)** - Upgrading from version 9 to 10

### For Content Creators
- **Creating Exercises**: See [TEMPLATES/](TEMPLATES/) directory for examples
  - `example-exercise.json` - Basic exercise template
  - `example-dropdown-closed-question.json` - Dropdown questions template
- **Adding Videos**: See [docs/VIDEO-FIELD-GUIDE.md](docs/VIDEO-FIELD-GUIDE.md)
- **Question Types**: All supported IELTS question types in [JSON-FORMAT-README.md](TEMPLATES/JSON-FORMAT-README.md)
- **Shortcodes**: See [docs/SHORTCODES.md](docs/SHORTCODES.md) for available shortcodes

### For Developers
- **Plugin Documentation**: See [docs/](docs/) directory
- **Template Files**: See `templates/` directory for PHP templates
- **Gamification Ideas**: See [GAMIFICATION_RECOMMENDATIONS.md](non-plugin-files/root-docs/GAMIFICATION_RECOMMENDATIONS.md) for enhancement suggestions
- **Progress Rings & Skills Radar**: See [PROGRESS_RINGS_AND_SKILLS_RADAR_GUIDE.md](non-plugin-files/root-docs/PROGRESS_RINGS_AND_SKILLS_RADAR_GUIDE.md) for using the new shortcodes

## 🎯 Common Questions

### "Where do I put the dropdown options?"
See **[DROPDOWN-QUESTION-FAQ.md](non-plugin-files/root-docs/DROPDOWN-QUESTION-FAQ.md)** for a complete answer with examples.

**Short answer:** Put them in the `mc_options` array, just like regular multiple choice questions. Use `[dropdown]` in your question text as a placeholder.

### "How do I import exercises?"
1. Create a JSON file using the format in [TEMPLATES/JSON-FORMAT-README.md](TEMPLATES/JSON-FORMAT-README.md)
2. In WordPress admin: Quizzes → Edit Quiz → Import from JSON section
3. Upload your JSON file

### "What question types are supported?"
- Open Question (Text Input)
- Closed Question (Multiple Choice)
- Closed Question Dropdown (Inline Dropdowns)

See [TEMPLATES/JSON-FORMAT-README.md](TEMPLATES/JSON-FORMAT-README.md) for full details.

## 📂 Directory Structure

```
├── TEMPLATES/                    # Example files and format guides
│   ├── JSON-FORMAT-README.md     # Complete JSON format reference
│   ├── example-exercise.json     # Basic exercise template
│   └── example-dropdown-closed-question.json
├── docs/                         # User documentation
│   ├── README.md                 # Documentation index
│   └── VIDEO-FIELD-GUIDE.md
├── includes/                     # PHP classes
├── templates/                    # PHP template files
├── assets/                       # CSS, JS, and images
├── main/                         # Main exercises and content
└── non-plugin-files/             # Non-runtime docs/tools moved out of root
```

## 🚀 Quick Start

1. **Install the plugin** in WordPress
2. **Create your first exercise**:
   - Download `TEMPLATES/example-exercise.json`
   - Modify it with your content
   - Import via WordPress admin
3. **For dropdown questions**: See [DROPDOWN-QUESTION-FAQ.md](non-plugin-files/root-docs/DROPDOWN-QUESTION-FAQ.md)

## 📖 More Resources

- **All Documentation**: See [docs/README.md](docs/README.md)
- **Version History**: See `non-plugin-files/root-docs/VERSION_*_RELEASE_NOTES.md` files
- **Development Guidelines**: See [DEVELOPMENT-GUIDELINES.md](non-plugin-files/root-docs/DEVELOPMENT-GUIDELINES.md)

## ❓ Getting Help

1. Check the relevant guide in this repository
2. Review the inline help text in the WordPress admin
3. Check existing documentation files
4. Open an issue on GitHub

---

## 📝 Changelog

### Version 15.28 (2026-02-08)
**Bug Fixes - Hybrid Mode Code Purchases**
- Fixed payment logging issue where debug panel showed "Last Payment: None found" even after successful payments
  - Corrected SQL query to use `created_at` column instead of non-existent `payment_date` column
- Fixed missing payment logging for PayPal code purchases
  - PayPal code purchases now properly logged to database (matching Stripe webhook behavior)
- Added comprehensive debug logging to both Stripe and PayPal code purchase flows
  - Easier to diagnose issues via error logs
  - Tracks permission checks, organization ID resolution, and code generation

**Impact**: These fixes ensure payment tracking works correctly in hybrid mode for both Stripe and PayPal, improving the debug panel's accuracy and making issue diagnosis much easier.

---

**Plugin Version**: 16.26
**WordPress Version Required**: 5.8+  
**PHP Version Required**: 7.2+
