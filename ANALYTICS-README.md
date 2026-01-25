# IELTS Analytics Plugin

A WordPress plugin that provides comprehensive analytics and reporting capabilities for the IELTS Course Manager plugin.

## Description

IELTS Analytics is a companion plugin for IELTS Course Manager that tracks and analyzes student progress, quiz performance, and course completion rates. It provides detailed insights and visualizations to help instructors understand student engagement and performance.

## Features

- **Student Progress Tracking**: Monitor individual student progress through courses and lessons
- **Quiz Performance Analytics**: Analyze quiz scores, completion rates, and performance trends
- **Course Completion Reports**: Track course completion statistics and identify bottlenecks
- **Event Logging**: Comprehensive event tracking system for all student activities
- **Custom Reports**: Generate detailed reports for instructors and administrators

## Installation

1. Upload the `ielts-analytics.php` file and the `includes-analytics` directory to your WordPress plugins directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Access analytics through the 'IELTS Analytics' menu in the WordPress admin dashboard

## Requirements

- WordPress 5.8 or higher
- PHP 7.2 or higher
- IELTS Course Manager plugin (recommended for full functionality)

## Database Tables

The plugin creates the following database table:

- `wp_ielts_analytics_events`: Stores all analytics events with user ID, event type, event data, and timestamp

## Usage

### Admin Dashboard

Access the analytics dashboard from the WordPress admin menu under "IELTS Analytics". The dashboard provides:

- Overview of key metrics
- List of available features
- Quick access to reports

### Settings

Configure plugin settings including:

- Data retention options
- Whether to delete analytics data on plugin uninstall

## Uninstalling

When you uninstall the plugin:

- By default, all analytics data is preserved
- If you enable "Delete Data on Uninstall" in settings, all data will be permanently removed

## Version

Current version: 1.0.0

## Author

IELTStestONLINE
- Website: https://www.ieltstestonline.com/

## License

GPL v2 or later
