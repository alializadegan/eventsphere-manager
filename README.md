# EventSphere Manager

Author: Ali Alizadegan  
License: GPLv2 or later  
Requires at least: WordPress 6.0  
Tested up to: WordPress 6.x  

---

## Overview

EventSphere Manager is a production-quality WordPress plugin that provides a complete event management system using WordPress best practices.

It includes:

- Custom Post Type for Events
- Event Type taxonomy
- Custom meta fields (Event Date, Location)
- Frontend archive and single templates
- UI-based filtering and search with AJAX
- RSVP system with validation and duplicate protection
- Admin panel to view and export RSVP entries
- REST API endpoints
- WP-CLI commands
- Admin settings page
- Email notifications
- Unit tests

This plugin is designed with scalability, security, and maintainability in mind.

---

## Installation

### Method 1 – Manual Installation

1. Download or clone this repository
2. Copy the `eventsphere-manager` folder into:


wp-content/plugins/


3. Go to WordPress Admin → Plugins
4. Activate **EventSphere Manager**

---

### Method 2 – Using ZIP file

1. Go to WordPress Admin → Plugins → Add New
2. Click "Upload Plugin"
3. Upload the plugin ZIP file
4. Activate the plugin

---

## Usage

After activation, the plugin automatically provides:

### Event archive page



/events


### Automatically created helper page



/events-list


This page contains the `[event_list]` shortcode and displays events with filters.

---

### Creating Events

Go to:



WordPress Admin → Events → Add New


Fill the following fields:

- Title
- Description
- Event Date
- Location
- Event Type
- Featured Image (required)

Publish the event.

---

### RSVP System

Users can register for events from the event single page.

The system includes:

- Email validation
- Duplicate registration prevention
- Error handling and feedback
- RSVP count tracking

---

### Filtering Events

Filtering is handled entirely via UI and supports:

- Search
- Event type
- Date range
- Status (Upcoming / Past / All)
- Sorting

Filtering works with:

- AJAX (without page reload)
- Fallback without JavaScript

---

## Admin RSVP Management

Admins can view registrations at:



WordPress Admin → Events → RSVPs


Features:

- View all registered users
- Filter by event
- Search by email or name
- View registration date
- Export registrations as CSV

Admins can also view RSVP entries directly from the Event edit screen.

---

## Settings Page

Available at:



WordPress Admin → Events → Settings


Configurable options:

- Events per page
- Enable/disable email notifications
- Default location suggestions

---

## Sample Data (WP-CLI)

Sample data can be generated using WP-CLI.

---

### Install helper page

```bash
wp wpem install
```
---

### Generate sample events

```bash
wp wpem seed --count=10
```
This will create test events for evaluation and testing.

---

### REST API Endpoints

Register RSVP:

```bash
POST /wp-json/wpemcli/v1/events/{id}/rsvp
```

Get RSVP count:

```bash
GET /wp-json/wpemcli/v1/events/{id}/rsvp-count
```

---

### Unit Testing

Unit tests are located in:
```
tests/
```
These tests validate:

- Custom post type registration

- Taxonomy registration

- Event date sanitization

- Event status logic

To run tests using WordPress PHPUnit environment:

```bash
phpunit
```

---

### Architecture and Best Practices

This plugin follows WordPress best practices:

- Object-oriented architecture

- Proper use of hooks and filters

- Secure input validation and sanitization

- Separation of concerns

- Scalable database design

- Admin and frontend separation

- REST API integration

- WP-CLI support


---

### Notes for Reviewers

This plugin was developed as part of a WordPress developer evaluation task.

It demonstrates:

- WordPress core knowledge

- Plugin architecture design

- Security best practices

- Frontend and backend development

- REST API integration

- WP-CLI integration

- Testing readiness
