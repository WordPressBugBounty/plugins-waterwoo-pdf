=== PDF Ink Lite - Free PDF Watermark & Password Protection ===
Contributors: canyonwebworks, littlepackage
Donate link: https://paypal.me/canyonwebworks
Tags: pdf watermark, woocommerce pdf, pdf password, document protection, digital downloads
Requires at least: 7.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 4.1.3
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Watermark and password protect PDFs with WooCommerce, EDD, and Download Monitor. No Ghostscript required, PHP 8+ compatible. Since 2014 (FKA WaterWoo)

== Description ==
PDF Ink Lite adds watermarks and password protection to every PDF your customers download from WooCommerce, Easy Digital Downloads, and Download Monitor. Watermarks can include customer-specific data like names, emails, and date, and are customizable with font face, font color, font size, vertical placement, and text.

PDF Ink Lite is the only free watermarker for WordPress which includes necessary libraries (so you don't have to ask your host to load them), and watermarks newer versions of PDFs (not just older versions).

= Features: =

* Choice of font face, color, size and placement (a horizontal line of text, centered anywhere on the page)
* Included font types cover most languages, and the plugin is internationalized
* Dynamic customer data inputs (customer first name, last name, email, order paid date, and phone) to customize PDFs on-the-fly
* Add a password to your PDF, and/or anti-copy, anti-print protections
* Watermark only designated PDF downloads (as specified by you), or *all* PDF downloads from your site
* Watermark is applied to **all** pages of the PDF ([upgrade to choose pages](https://pdfink.com/ "Upgrade to the full version"))
* Accommodates different page/paper sizes, and PDFs with various page sizes (letter, A4, legal, etc)
* Secure & encapsulated: PDF Ink hooks into e-commerce download link clicks and checks for PDFs and maybe marks them - that's it!

For better PDF coverage and many more options, [check out PDF Ink, the full version of PDF Ink Lite](https://pdfink.com/ "PDF Ink").

= Upgraded (paid) version features: =

* Clean, robust settings panels and a separate watermarking testing suite
* Watermark all PDF files with same settings OR set individual watermarks/passwords per product or even per product variation
* Begin watermark on selected page of PDF document (to avoid watermarking a cover page, for example), and/or select end page
* Watermark every page, odd pages, even pages, or ranges of pages
* Unlimited rotatable watermark locations on one page, anywhere on the page
* Additional dynamic customer data input (business name, address, order number, product name, quantity of product purchased), and filter hooks for adding your own
* Semi-opaque (transparent) watermarks - hide your watermarks completely if desired
* RTL (right to left) watermarking
* Use of some HTML tags to style your output, including text-align CSS styling (right, center, left is default), links (&lt;a&gt;), bold (&lt;strong&gt;), italic (&lt;em&gt;)...
* Additional text formatting options, such as font color and style (bold, italics) using HTML
* Line-wrapping, forced breaks with &lt;p&gt; and &lt;br /&gt; tags
* Upload and use your own font for stamping. Also, hooks to further customize font use
* Higher level PDF protections with AES encryption and extended file protection settings
* Keep original and/or add file metadata
* Edit or add PDF annotations, add embedded streams, and edit PDF outgoing (URI) links
* Open ZIP files and mark PDF files inside the archive
* Works with EPUB! Stamp EPUB files with customized text
* Shortcode for creating PDF download links for any page (no need for e-commerce plugin)
* Embed marked/encrypted files on the page, using ADOBE SDK embed or PDF Object JavaScript embed.
* Test watermark and/or manually watermark a file on the fly, from the admin panel
* Preserves external embedded PDF links despite watermarking; internal links (ToC) are not reliably preserved ([add SetaPDF-Stamper to PDF Ink](https://pdfink.com/?source=wordpress) for this feature)
* Filter hooks to add 1D and 2D barcodes (including **QR codes**)
* Remove stamped files from your server after stamping, or on a schedule

[PDF Ink is priced below competitor plugins that offer _maybe_ half the function.](https://pdfink.com/ "PDF Ink")  Why? Because we want you to succeed! 🥰

== Installation ==

= Minimum Requirements =

* WordPress 7.0 or greater
* WooCommerce 8.2 and newer
* PHP version 7.4 or greater
* PDFs version under 2.0

Please use the most recent version of all WordPress software - it's what we support!

= We recommend your host supports: =

* WordPress Memory limit of 64 MB or greater (usually <=512MB works fine)
* PHP max_execution_time up to 60 seconds (30 should be fine)
* If you have large PDF files and/or heavy download traffic, you may need to pay for beefier hosting with more CPUs. A shared hosting plan might not cut it.
* OpenSSL

= To install plugin =
1. Upload the entire "waterwoo-pdf" folder to the "/wp-content/plugins/" directory.
2. Activate the "PDF Ink Lite" plugin through the Plugins menu in WordPress.
3. Visit WooCommerce->Settings->PDF Ink Lite tab to set your plugin preferences (OR Easy Digital Downloads -> Settings -> Extensions -> PDF Ink Lite OR Download Monitor Downloads -> Settings -> PDF Ink Lite).
4. Test your watermarking by making mock purchases before going live to make sure it works and looks great!

= To remove plugin: =

1. Deactivate plugin through the 'Plugins' menu in WordPress
2. Delete plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= I can't donate and I cannot upgrade. How can I still support you? =

&nbsp;
Thanks for thinking of the countless volunteers who develop plugins for you to enjoy!

In the PDF Ink Lite "Housekeeping" settings, you can check the "Attribution" box. This will add a super tiny, invisible watermark to page 2 of your PDF files, linking back to PDF Ink. The attribution mark is almost impossible to spot -- try it!

= Where do I change PDF Ink Lite settings? =

&nbsp;
You can find the PDF Ink settings page by clicking on the "Settings for XXX" link under the PDF Ink Lite plugin title on your WordPress plugins panel.

= Something is wrong =

&nbsp;
Here are some basic troubleshooting steps to start with. Below those is a link to further recommendations.
&nbsp;

1. Is WooCommerce or Download Monitor or Easy Digital Downloads installed, and do you have a purchasable PDF product in your shop to watermark?
2. Have you checked the PDF Ink Lite settings checkbox to "Enable watermarking?"
3. Make sure your PDF product downloads work without PDF Ink Lite activated, to narrow the problem.
4. Try watermarking a different PDF (one you didn't create) to narrow the problem.
5. Try using a different font (in settings).
6. Turn on the PDF Ink Lite debugging in the settings, and review the logs after trying again.
7. Increase your PHP time limit and memory limits if they are set low (but don't set too high, either). Server limitations can stop this plugin from functioning well.
8. Try a different PHP version.
9. Read more below under ["Why does the watermark go off the page, create blank pages?"](https://wordpress.org/plugins/waterwoo-pdf/#why%20does%20the%20watermark%20go%20off%20the%20page%2C%20create%20blank%20pages%3F).

&nbsp;
Please - definitely - get in touch with your issues via the WordPress.org support forum before leaving negative feedback about this free plugin.

[To request help using the WordPress.org support forum, start here](https://wordpress.org/support/topic/before-you-post-2026-support-tips-please-read/).

**Do not use the WordPress.org support forum for help with the full (paid) version of PDF Ink** - that is against WordPress.org rules. Conversely, use the WordPress.org support channel -- not email -- for PDF Ink Lite (free).

= My watermark isn’t English =

&nbsp;
Try selecting a different font like “Deja Vu” in the plugin settings panel.

One reason watermarks might not show up is because the watermark contains special characters but you're using a font which doesn’t support those characters. If none of the included fonts are subsetted for your language characters, you will need to programmatically add fonts yourself or look into purchasing the full version of this plugin, which has more built-in fonts and supports font uploads.

= How do I test my watermark? =

&nbsp;
Maybe set your PDF to $0 (free) and "Privately Published" (for WooCommerce). Or maybe create a coupon in your shop to allow 100% free purchases. Don't share this coupon code with anyone! Test your watermark by purchasing PDFs from your shop using the coupon. It's a bit more tedious. If you want easier on-the-fly testing, purchase the full version of this plugin.

= Why does the watermark go off the page, create blank pages? =

&nbsp;
Your watermark text string is too big or long for the page, and goes off it! Try decreasing font size or using the Y fine tuners to move the watermark back onto the page. Try lowering your "y-axis" value. This number corresponds to how many *millimeters* you want the watermark moved down the page. For example, if your PDF page is 11 inches tall, your Y-axis setting should be a deal less than 279.4mm in order for a watermark to show. The built-in adjustments on the settings page ultimately allow for watermarking on all document sizes. You may need to edit your watermark if it is too verbose.

You can use a negative integer value for your Y-tuner and measure up from the bottom of the page. This is especially helpful if your PDF has variable sized pages.

= Where do the watermarked files go? =

&nbsp;
They are generated with a unique name and stored in the same folder as your original WordPress/Woo product media upload (usually wp-content/uploads/year/month/file). The unique name includes the order number and a time stamp. If your end user complains of not being able to access their custom PDF for some reason (most often after their max number of downloads is exceeded), you can find it in that folder, right alongside your original.

If you are using Woo FORCED downloads, the plugin attempts to delete the watermarked files after being delivered. This isn't 100% reliable since it works on PHP shutdown. If you don't like attempted deletion, you can change it with the 'wwpdf_do_cleanup' filter hook (set it to FALSE). The paid version of this plugin allows you to choose how/if marked files are removed.

= Is there a fallback in case watermarking fails? =

&nbsp;
Yes, you can serve the file untouched if watermarking fails, and avoid any error messages, by using the following filter code in your (child) theme functions.php file:

`add_filter( 'wwpdf_serve_unwatermarked_file', '__return_true' );`

If you do not know how to edit your functions.php file, you can use the Code Snippets plugin to easily add this code to your WP site frontend.

== Screenshots ==

1. Settings page screenshot, showing where to turn on the plugin and choose files.
2. Settings page screenshot, having to do with font choices and watermark content. Shortcodes are in use and will be converted dynamically to actual customer information.
3. Settings page screenshot, showing password and protections settings. RC4 40-bit encryption is set automatically in PDF Ink Lite if protections are selected. For higher encryption, upgrade.

== Upgrade Notice ==

= 4.0 =
* If you have overwritten parts of this plugin or are using filter hooks, this could be a breaking update. In that case we recommend you take backups and work on a non-production server to navigate your upgrade

== Changelog ==

= 4.1.3 - 7 September 2026 =
* Nuanced file handling for WooCommerce v. Easy Digital Downloads v. Download Monitor

= 4.1.2 - 22 August 2026 =
* Confirming compatibility with WP 7.1

= 4.1.1 - 18 August 2026 =
* Fix "Union types allowed PHP > 8.0" in cynpdi.php, since we are still PHP 7.4 compatible

= 4.1.0 - 17 August 2026 =
* Tweak to parse_file_path() method to remove redundant check and improve Bedrock/etc. compatibility
* Bumped WP (7.0) and WC (8.2) required versions to match required PHP version (7.4)
* Change namespacing (sorry) & update copyrights on library files
* Define over-written TCPDF constants early in wwpdf-watermark.php
* Deprecate `wwpdf_filter_watermarked_file` hook in the free version. While I love open source, I also need to be able to support myself. The paid version includes many filter hooks for developers. Thanks for understanding.
* Show (disabled) library select in settings panel
* Phone home in settings panels for plugin news, if any

= 4.0.13 - 29 May 2026 =
* Tiny improvements for PHP 8.0+ compatibility
* Testing with WP 7.0 & WC 10.8

= 4.0.12 - 29 March 2026 =
* Improvement to how file path constant 'PDFINK_LITE_UPLOADS_PATH' is set
* Testing with WC 10.6

= 4.0.11 - 11 March 2026 =
* Update TCPDF to version 6.11.2 - will help with (but not guarantee) PHP 8.5 compatibility
* Update namespace vendor in TCPDI/TCPDF libraries to CanyonWebworks

= 4.0.10 - 10 March 2026 =
* Remove cache-busting (it's redundant)
* Update contributors, lang files, testing with WC 10.5

= 4.0.9 - 22 Jan 2026 =
* Bust WP PDF Ink settings cache when watermarking settings changed
* Replace \r\n and \r with \n in EDD textareas while saving
* Remove EDD settings on plugin uninstall using edd_delete_option()

Older changes are found <a href="https://plugins.svn.wordpress.org/waterwoo-pdf/trunk/changelog.txt">in the changelog.txt file in the plugin directory.</a>