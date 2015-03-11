=== Plugin Name ===
Contributors: mightydigital, farinspace
Tags: private, member, membership, corporate, business
Requires at least: 4.0
Tested up to: 4.1.1
Stable tag: 0.2.0
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Creates a fenced membership area with private content.

== Description ==

The Partners plugin creates a fenced membership area (separate from the built-in WordPress Users)
allowing you to manage a clean WordPress installation for your company's content management system (CMS).

The plugin exposes several shortcodes:

[partners_login_form]
[partners_registration_form]
[partners_forgot_password_form]
[partners_reset_password_form]

[partners_is_authenticated]
[partners_is_not_authenticated]

== Installation ==

The easiest way to install the plugin is to:

1. Login to your WordPress installation
1. Go to the Plugins section and click "Add New"
1. Perform a search for "Partners"
1. Locate the Partners plugin by Mighty Digital
1. Click the "Install Now" button and click "Ok" to confirm
1. Click "Activate Plugin" or activate the plugin from the Plugins section

If you've downloaded the latest plugin files:

1. Upload the Partners plugin folder to the /wp-content/plugins/ directory
2. Activate the plugin from the Plugins section

== Frequently Asked Questions ==

= Can I put the login form on any page? =

Yes, simply use the [partners_login_form] shortcode.

= How do I show content only if a member is logged in? =

To do this, wrap your private content with the [partners_is_authenticated] YOUR CONTENT [/partners_is_authenticated] shortcode.

== Screenshots ==

1. Manage registered members
2. Robust settings
3. Assisted default page creation

== Changelog ==

= 0.2.0 =
* fixed preload issue

= 0.1.0 =
* initial release
