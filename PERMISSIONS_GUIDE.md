# SRC Management System - Permissions Guide

This document explains the role-based access control system implemented in the SRC Management System.

## Overview

The system implements a role-based access control (RBAC) system with three user roles:

1. **Admin** - Full access to all features (CRUD operations)
2. **Member** - Read access to most features, limited create/update access
3. **Student** - Read access to basic features, can submit feedback

## Permission System Structure

The permission system is based on two main components:

1. **User Roles** - Each user is assigned one role (admin, member, student)
2. **Resource Permissions** - Each role has specific permissions for each resource

### Resources

The system manages the following resources:

- users
- departments
- portfolios
- events
- news
- documents
- minutes
- elections
- budget
- reports
- feedback
- settings

### Actions

For each resource, users can have permission to perform the following actions:

- **create** - Add new items
- **read** - View items
- **update** - Modify existing items
- **delete** - Remove items

## Permission Matrix

Below is the permission matrix showing which roles can perform which actions on each resource:

| Resource    | Admin                | Member               | Student              |
|-------------|----------------------|----------------------|----------------------|
| users       | create, read, update, delete | read                 | -                    |
| departments | create, read, update, delete | read                 | read                 |
| portfolios  | create, read, update, delete | read                 | read                 |
| events      | create, read, update, delete | read                 | read                 |
| news        | create, read, update, delete | read                 | read                 |
| documents   | create, read, update, delete | read                 | read                 |
| minutes     | create, read, update, delete | read                 | -                    |
| elections   | create, read, update, delete | read                 | read                 |
| budget      | create, read, update, delete | read                 | -                    |
| reports     | create, read, update, delete | read                 | -                    |
| feedback    | read, update          | create, read         | create               |
| settings    | read, update          | -                    | -                    |

## Special Cases

1. **Feedback** - This is a special case where:
   - Students and members can create feedback
   - Members can view their own feedback
   - Admins can view all feedback and respond to it, but cannot create feedback

2. **Own Content** - Users can have additional permissions for content they created:
   - Members can edit/delete their own feedback
   - Members may have permission to edit their own submissions in some modules

## Implementation

### Required Files

1. `auth_functions.php` - Contains all authentication and permission functions
2. `db_config.php` - Database connection and helper functions
3. `access_denied.php` - Page to display when access is denied

### How to Check Permissions

Use the following functions from `auth_functions.php`:

#### Basic Authentication Checks

```php
// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check if user is admin
if (isAdmin()) {
    // Admin-only code here
}

// Get current user info
$currentUser = getCurrentUser();
```

#### Permission Checks

```php
// Check if user has permission for an action on a resource
if (hasPermission('read', 'events')) {
    // User can read events
}

// Force permission check with redirect
requirePermission('update', 'events', 'login.php');

// Check if user owns a resource
if (ownsResource('feedback', $feedbackId)) {
    // User owns this feedback
}

// Check permission for a specific resource instance
if (hasResourcePermission('update', 'feedback', $feedbackId)) {
    // User can update this specific feedback
}
```

### Template Files

Use these template files as starting points for your pages:

1. `admin_template.php` - For admin-only pages
2. `user_template.php` - For pages accessible to all users with proper permission checks

## Implementing in Page Files

### Step 1: Include Required Files

```php
require_once '../auth_functions.php';
require_once '../db_config.php';
```

### Step 2: Check Authentication

```php
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}
```

### Step 3: Check Permissions

```php
// For admin-only pages
if (!isAdmin()) {
    header("Location: access_denied.php");
    exit();
}

// OR for pages with resource-specific permissions
if (!hasPermission('read', 'events')) {
    header("Location: access_denied.php");
    exit();
}
```

### Step 4: Implement Conditional UI Elements

```php
// Show admin controls only if user has permission
if (hasPermission('create', 'events')) {
    // Show "Add Event" button
}

// Show edit/delete buttons only for admins or content owners
if (isAdmin() || ownsResource('feedback', $feedbackId)) {
    // Show edit/delete buttons
}
```

## Example: Implementing in a Page

Here's an example of how to implement permissions in an event listing page:

```php
<?php
// Include required files
require_once '../auth_functions.php';
require_once '../db_config.php';

// Check authentication
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check permissions
if (!hasPermission('read', 'events')) {
    header("Location: access_denied.php");
    exit();
}

// Get current user
$currentUser = getCurrentUser();
$isAdmin = isAdmin();

// Page logic...
$events = fetchAll("SELECT * FROM events ORDER BY event_date DESC");
?>

<!-- HTML content -->
<div class="card">
    <div class="card-header">
        <h5>Events</h5>
        <?php if (hasPermission('create', 'events')): ?>
        <a href="add_event.php" class="btn btn-primary">Add Event</a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <!-- List events -->
        <?php foreach ($events as $event): ?>
        <div class="event-item">
            <h4><?php echo htmlspecialchars($event['title']); ?></h4>
            <p><?php echo htmlspecialchars($event['description']); ?></p>
            
            <!-- Admin actions -->
            <?php if (hasPermission('update', 'events')): ?>
            <a href="edit_event.php?id=<?php echo $event['event_id']; ?>" class="btn btn-sm btn-info">Edit</a>
            <?php endif; ?>
            
            <?php if (hasPermission('delete', 'events')): ?>
            <a href="delete_event.php?id=<?php echo $event['event_id']; ?>" class="btn btn-sm btn-danger">Delete</a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
```

## Security Considerations

1. **Always check permissions server-side** - Don't rely only on hiding UI elements
2. **Check permissions before any database operation** - Verify permissions before create/update/delete actions
3. **Use prepared statements** - All database queries use prepared statements to prevent SQL injection
4. **Check ownership for user-specific content** - Use `ownsResource()` to verify a user owns the content they're trying to modify

## Modifying Permissions

To modify the permission system, edit the `hasPermission()` function in `auth_functions.php`. The permissions are defined in the `$permissions` array.

```php
$permissions = [
    'admin' => [
        'users' => ['create', 'read', 'update', 'delete'],
        // ...
    ],
    'member' => [
        'users' => ['read'],
        // ...
    ],
    'student' => [
        'users' => [],
        // ...
    ],
];
```

To add a new resource or modify permissions, simply update this array with the appropriate values. 