=== LazyCaptcha ===
Contributors: tuxlog
Donate link: https://www.tuxlog.de
Tags: captcha, comment, math, arithmetic, antispam
Requires PHP: 7.2
Requires at least: 5.0
Tested up to: 6.6
Stable tag: 0.6
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html


LazyCaptcha is a small and lazy plugin to prevent bots from spamming your comments.

== Description ==
LazyCaptcha is a small and lazy plugin to prevent bots from spamming your comments.
It generates a random image with an easy arithmetic task and checks if the result is correct after the comment is trasmitted.
It stores the tasks and results in the WordPress database and deletes them after use or two times a day using a wp-cron job.
It works perfectly on my sites, so I decided to publish it for others on WordPress.org.


== Installation ==

0. Install via the WordPress plugin admin dialog (search for LazyCaptcha)


== Translations ==

   LazyCaptcha comes in german and english. Feel free to translate it to other languages and send me the translation so I can include it into the translation repository


== Frequently Asked Questions ==
= Where can I setup LazyCaptcha? =
No where, LazyCaptcha comes without any settings. It is just as it is. If you want to change the layout you can customize the css classes in wplc.css

== Screenshots ==
1. LazyCaptcha in action


== Changelog ==

= v0.6 (2024-07-06) =
* added check for dorect calls on wp-comments-post.php

= v0.5 (2024-06-09) =
* restructured function cut

= v0.4 (2023-03-13) =
* coming closer to WordPress coding standards
* fixed some typos
* added nonce verification

= v0.3 (2022-07-17) =
* fixed work around lazycaptcha by editing a special tag

= v0.2 (2021-03-06) =
* corrected calling locations
* fixed wpdb->prepare use

= v0.1 (2021-03-04) =
* initial release
