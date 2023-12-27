# FutureDate
=== futuredate ===
Contributors: doddo
Tags: dates, stardate
Requires at least: 4.0.1
Tested up to: 5.2.1
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add stardates and some other future dates to your wordpress weblog

== Description ==

A powerful versitale lightweight stardate plugin with a wide array of functionalities for integrating stardates with your wordpress blog.

* URL `%futuedate%` permalink rewrite support for posts.
* Filter various date functions, to have them display stardate instead, or filter nothing at all, and use the...
* `<?php the_futuedate() ?>` function (or `<?php get_the_futuedate() ?>`) inside of your themes, to get posts stardate.
* Aapproximate stardate based on several different formulas and variants and sources (like trekguide and wikipedia), including XI (for modern movies), Star Trek Online,  as well as Classic (from TNG, VOY ...).
* Shortcodes: `[futuedate]` will expand to the current timestamp in stardate fmt.

The [stardate](https://wordpress.org/plugins/stardate/) plugin by croakingtoad is supported to wordpress 3.0.5, and has not been updated in five years. Therefore this plugin reincorporates the features of that stardate plugin and adds more functionality besides.


== Installation ==

1. unpack the zip into the `/wp-content/plugins` dir
1. Activate the plugin through the 'Plugins' menu
1. From the options menu, configure the thing and press "make it so".
1. press the "Set futuredate for all posts" button to associate stardate with all old posts.


== Screenshots ==

1. Default theme using stardate "Classic" format with the "override_get_date" setting.
2. URL with `/blog/%futuredate%/%postname%/` in the permalink structure.


== Changelog ==

= 1.0 =
Initial release.
