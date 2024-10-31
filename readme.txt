=== Mythic Beasts WordPress Management ===
Requires at least: 4.0
Tested up to: 6.4
Stable tag: 1.7.0
License: GPLv2 or later

Enables data collection as part of the Mythic Beasts Managed WordPress Hosting service.

== Description ==

This plugin enables automated data collection as part of the third party Mythic Beasts Managed WordPress Hosting service, and provides relevant notices to administrative users. Without this service the plugin will have very limited functionality.

When enabled, it stores a randomly generated key in the WordPress options, then allows querying the following information when that key is provided:

* IP Address of the server, and an identifier which is generated from the IP address and sites install path.
* System User Name, user and group ID numbers WordPress is running as.
* PHP Version, handler and extentions available to WordPress.
* Location of WordPress on the server, along with URLs it's configured with.
* WordPress core version and automatic update setting.
* Email address of the configured WordPress administrator.
* A list of Plugins installed, and information about available updates.
* A list of Themes installed, and information about available updates.
* Hashes of the core, plugin and theme files.
* Various other variables.
* A timestamp of when this information was last queried.

No information is sent automatically, and this plugin only outputs the data when the stored key is provided in a normal web request.

More information on the Mythic Beasts Managed WordPress Hosting service is available at https://www.mythic-beasts.com/apps/wordpress.

Terms and Conditions as well as the Privacy Policy for this service are avaialble at https://www.mythic-beasts.com/terms/overview.

== Installation ==

Users will not typically need to install this, as it will usually be added as part of the management of the site.

Installation however is simply a case of uploading the plugin to your blog and activating it.

A key to access the data will then be stored in the WordPress options, and that will be retrieved automatically and used to query the site as part of the service from Mythic Beasts.

== Changelog ==

= 1.7.0 =
* Disable reporting Object Cache on Site Health page, as it's handled elsewhere.

= 1.6.1 =
* Fix reporting Cron status

= 1.6.0 =
* Report memory limits
* Files/directories to test file security

= 1.5.0 =
* Report maximum file upload size
* Small bugfixes

= 1.4.0 =
* Report wp-cron status
* Report database host, name and user
* Report basic OS info

= 1.3.0 =
* Prevent potentially breaking automatic upgrades for the core plugins and themes.
* Report new user settings and role counts.

= 1.2.0 =
* Small fixes and workarounds for edge cases.
* Added reporting of htaccess.

= 1.1.3 =
* Improved handling of non UTF-8 data in plugins and themes.

= 1.1.2 =
* Further small fixes.

= 1.1.1 =
* Fixed issue collecting plugin data in some situations.

= 1.1.0 =
* Significant upgrades and improvements to data provided.
* Added option to also retrieve file hashes.

= 1.0.6 =
* Added further escaping for plugin and theme data to ensure valid JSON.

= 1.0.5 =
* Moved hook earlier to pick up data from sites with authentication plugins.

= 1.0.4 =
* Adjusted hook to pick up sites which require logins.

= 1.0.3 =
* Adjustments to output.

= 1.0.2 =
* Removed unused function.
* Reformated activate/deactivate function names.

= 1.0.1 =
* Escaping and sanitization improvements.
* Moved from GET to POST queries for retrieval.

= 1.0.0 =
* Initial version.

