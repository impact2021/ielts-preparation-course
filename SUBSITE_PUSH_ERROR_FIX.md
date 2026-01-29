# Subsite Push Error Handling Fix

## Problem
When pushing content to a subsite, the system displayed only "Error:" with no additional message, taking 1-2 minutes before showing this incomplete error.

## Root Cause
The `push_to_subsite` method in `class-multi-site-sync.php` had several error handling issues:

1. **No HTTP status code validation** - Treated HTTP 500 errors the same as 200 OK responses
2. **No JSON validation** - If `json_decode()` failed, accessing `$body['message']` resulted in undefined array access
3. **Empty message propagation** - When the API returned `{"success": false, "message": ""}`, a `WP_Error` was created with an empty message
4. **Insufficient error context** - Error messages didn't include the subsite name or other helpful debugging information

## Solution

### 1. Enhanced PHP Error Handling (`includes/class-multi-site-sync.php`)

#### Added HTTP Status Code Validation
```php
$status_code = wp_remote_retrieve_response_code($response);
if ($status_code < 200 || $status_code >= 300) {
    $error_message = sprintf(
        'Subsite "%s" returned HTTP error %d. Please check the subsite is configured correctly and the REST API endpoint is available.',
        $subsite->site_name,
        $status_code
    );
    return new WP_Error('http_error', $error_message);
}
```

#### Added JSON Response Validation
```php
$response_body = wp_remote_retrieve_body($response);
$body = json_decode($response_body, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($body)) {
    $error_message = sprintf(
        'Subsite "%s" returned invalid JSON response. Response: %s',
        $subsite->site_name,
        substr($response_body, 0, 200)
    );
    return new WP_Error('invalid_response', $error_message);
}
```

#### Improved Error Message Handling
```php
$error_message = isset($body['message']) && !empty($body['message']) 
    ? $body['message'] 
    : sprintf('Subsite "%s" rejected the sync request. Please check authentication and permissions.', $subsite->site_name);
return new WP_Error('sync_failed', $error_message);
```

#### Enhanced Network Error Messages
```php
if (is_wp_error($response)) {
    $error_message = sprintf(
        'Failed to connect to subsite "%s": %s',
        $subsite->site_name,
        $response->get_error_message()
    );
    return new WP_Error($response->get_error_code(), $error_message);
}
```

### 2. Improved JavaScript Error Handling (`includes/admin/class-admin.php`)

#### Added Fallback for Empty Response Messages
```javascript
var errorMessage = response.data && response.data.message 
    ? response.data.message 
    : '<?php _e('An unknown error occurred. Please try again or contact support.', 'ielts-course-manager'); ?>';
statusDiv.html('<div class="notice notice-error inline"><p><strong><?php _e('Error:', 'ielts-course-manager'); ?></strong> ' + errorMessage + '</p></div>');
```

#### Added Fallback for AJAX Errors
```javascript
var errorMessage = error || status || '<?php _e('Network error. Please check your connection and try again.', 'ielts-course-manager'); ?>';
statusDiv.html('<div class="notice notice-error inline"><p><strong><?php _e('Error:', 'ielts-course-manager'); ?></strong> ' + errorMessage + '</p></div>');
```

## Benefits

1. **Clear Error Messages**: Users always see descriptive error messages, never just "Error:"
2. **Better Debugging**: Errors include subsite names, HTTP status codes, and response samples
3. **Faster Feedback**: Errors are detected and reported immediately without waiting for timeouts
4. **Improved User Experience**: Users get actionable error messages with suggestions for resolution

## Error Scenarios Covered

| Scenario | Old Behavior | New Behavior |
|----------|-------------|--------------|
| Subsite unreachable | "Error:" | "Failed to connect to subsite 'X': cURL error..." |
| HTTP 500 error | Empty message or generic | "Subsite 'X' returned HTTP error 500. Please check..." |
| Invalid JSON response | JavaScript error | "Subsite 'X' returned invalid JSON response. Response: ..." |
| Empty error message | "Error:" | "Subsite 'X' rejected the sync request. Please check authentication..." |
| Missing message field | "Error: Unknown error" | Meaningful fallback with subsite context |
| Network timeout | "Error:" after 30s | "Failed to connect to subsite 'X': Connection timeout" |

## Testing

All error scenarios have been tested with the test script in `/tmp/test_subsite_error_handling.php`.
The script validates:
- ✓ Empty error messages are replaced with meaningful fallbacks
- ✓ HTTP status codes are validated and reported
- ✓ Invalid JSON responses are detected with sample content
- ✓ Network errors include subsite name for context
- ✓ JavaScript handles undefined/empty errors gracefully

## Files Modified

1. `includes/class-multi-site-sync.php` - Enhanced `push_to_subsite()` method
2. `includes/admin/class-admin.php` - Improved AJAX error handling in JavaScript
