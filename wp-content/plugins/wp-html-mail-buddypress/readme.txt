=== BuddyPress Email Template Designer - WP HTML Mail ===
Contributors: haet
Tags: email, template, html mail, mail, message, buddypress
Requires at least: 5.0
Tested up to: 5.5.1
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Simply customize email templates for BuddyPress

== Description ==

BuddyPress gives you some customization options for your emails by default but there are still some downsides:

* Styling options are very limited
* It uses its own mailer which doesn’t work very well with SMTP plugins
* If you use an SMTP plugin BuddyPress emails switch to plain text
* You can’t use the same email design for BuddyPress and other WordPress plugins

= Features =

* **Email design for desktop, mobile and webmail clients**
Create your template once and it looks great on every device
* **One template for all your WordPress emails**
WP HTML Mail is an all-in-one email solution. You can use the same template for your contact form emails (NinjaForms, WPForms, Contact Form 7, Gravity Forms, Caldera Forms, …) as well as your store ([WooCommerce](https://codemiq.com/en/downloads/wp-html-mail-woocommerce/) and [Easy Digital Downloads](https://codemiq.com/en/downloads/wp-html-mail-easy-digital-downloads/)) and of course WordPress own notifications (registration, comments, updates, …) 
* **No coding required (but possible)**
You can customize your html emails without writing a single line of code but there are a few [filters](https://wordpress.org/plugins/wp-html-mail/#faq-header) for additional expert customizations.
* **Live preview**
Preview your email design live in your browser or send a test mail to get the best results.

= BuddyPress PHPMailer and SMTP delivery =

BuddyPress uses a customized instance of PHPMailer to send multipart email (plain text AND HTML at the same time). To make our email design work we have to switch to wp_mail() function which doesn’t work with multipart messages. On the other hand side this makes it possible to use SMTP plugins like Post SMTP Mailer, WP Mail SMTP and many more with BuddyPress.


[read more about WP HTML Mail >](https://wordpress.org/plugins/wp-html-mail/)


== Installation ==
Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page.


== Changelog ==

= 1.0.1 =
* updated compatibility with current WP versions

= 1.0 =
* initial release

== Upgrade Notice ==

== Frequently Asked Questions ==

== Screenshots ==

1. Endless possibilities