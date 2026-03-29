=== PDF Ink Lite - Free PDF Watermark & Password Protection ===
Contributors: canyonwebworks, littlepackage
Donate link: https://paypal.me/canyonwebworks
Tags: pdf watermark, pdf password, woocommerce pdf, document protection, digital downloads
Requires at least: 4.9
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 4.0.12
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Watermark and password protect PDFs with WooCommerce, EDD, and Download Monitor. No Ghostscript required, PHP 8+ compatible. Since 2014 (FKA WaterWoo)

PDF Ink Lite is the only free watermarker for WordPress which includes necessary libraries (so you don't have to ask your host to load them), and watermarks newer versions of PDFs (not just older versions).

== Description ==
PDF Ink Lite adds watermarks and password protection to every PDF your customers download from WooCommerce, Easy Digital Downloads, and Download Monitor. Watermarks can include customer-specific data like names, emails, and date, and are customizable with font face, font color, font size, vertical placement, and text.

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

* WordPress 5.6 or greater
* WooCommerce 5.0 and newer
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
Oh, thanks for thinking of the countless volunteers who develop plugins for you to enjoy!

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
3. Have you entered your PDF file names correctly in the second field if you've entered any at all? This field is case-sensitive.
4. Make sure your PDF product downloads work without PDF Ink Lite activated, to narrow the problem.
5. Try watermarking a different PDF (one you didn't create) to see if that PDF works.
6. Try using a different font (in settings).
7. Using cutting-edge PHP? Try a lower PHP version.
8. Increase your PHP time limit and memory limits if they are set low (but don't set too high, either). Server limitations can stop this plugin from functioning well.
9. Read more below under ["Why does the watermark go off the page, create blank pages?"](https://wordpress.org/plugins/waterwoo-pdf/#why%20does%20the%20watermark%20go%20off%20the%20page%2C%20create%20blank%20pages%3F).

&nbsp;
Please - definitely - get in touch with your issues via the WordPress.org support forum before leaving negative feedback about this free plugin.

[To request help using the WordPress.org support forum, start here](https://wordpress.org/support/topic/before-you-post-2026-support-tips-please-read/).

**Do not use the WordPress.org support forum for help with the full (paid) version of PDF Ink** - that is against WordPress.org rules. Conversely, use the WordPress.org support channel -- not email -- for PDF Ink Lite (free).

= My watermark isn’t English =

&nbsp;
Try selecting a different font like “Deja Vu” in the plugin settings panel.

One reason watermarks might not show up is because the watermark contains special characters but you're using a font which doesn’t support those characters. If none of the included fonts are subsetted for your language characters, you will need to programmatically add fonts yourself or look into purchasing the full version of this plugin, which has many more built-in fonts and supports font uploads.

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

If you are using Woo FORCED downloads, the plugin attempts to delete the watermarked files after being delivered. This isn't 100% reliable since it works on PHP shutdown. If you don't like attempted deletion, you can change it with the 'wwpdf_do_cleanup' filter hook (set it to FALSE). The paid version of this plugin has improved file handling/removal.

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

= 4.0.8 - 19 December 2025 =
* Update TCPDF to version 6.10.1
* Testing with WP 6.9 and WC 10.4
* Testing with PHP 8.5
* Deprecation notices for several filter hooks

= 4.0.7 - 2 December 2025 =
* Fix - check for existence of function 'edd_get_file_download_method' during auto temp file deletion

= 4.0.6 - 1 December 2025 =
* Fix - automatic temp file deletion (when used with WooCommerce forced and EDD forced file delivery)

= 4.0.5 - 21 November 2025 =
* Fix - move load_plugin_textdomain() to 'init' hook
* Tweak - provide debug log feedback for people getting unexpected white bars on PDF (answer: upgrade)
* Tweak - update/add translations
* Upgrade TCPDF library to version 6.10.0
* Testing with WC 10.3

= 4.0.4 - 4 September 2025 =
* Fix for new email as password feature
* Bump minimum WC version to 6.5 (to match plugin PHP 7.2 requirement)

= 4.0.3 - 26 July 2025 =
* Tweak for TCPDI parser to reach more PDFs
* Tweak - move set font color and size outside page loop
* Remove `wwpdf_skip_watermarking`, wwpdf_dont_watermark_this_page`, and `wwpdf_public_key` filter hooks. Sorry, it's free!
* Testing with WC 10.0

= 4.0.2 - 21 June 2025 =
* Fix for some PDFs with line breaks between objects

= 4.0.1 - 27 May 2025 =
* Fix WooCommerce [EMAIL] shortcode gone missing

= 4.0.0 - 27 May 2025 =
* Integration with Download Monitor & Easy Digital Downloads
* Move created files out to an independent folder in wp-content/uploads/ for easier file management; no more file name changes!
* Give user ability to set left/right margins in plugin settings
* Update TCPDF library to version 6.9.4; fixed some parsing bugs in tcpdi_parser class
* The `wwpdf_filter_file_path`, `wwpdf_font_decode`, and `wwpdf_out_charset` filter hooks removed from the free version; upgrade at pdfink.com to continue using them
* Fonts added: Dejavu Sans, Dejavu Serif, Symbol, and Zapf Dingbats 👾
* PDF Ink Lite now sets attribution in PDF files it generates. Remove this by upgrading!

= 3.6.0 - 19 January 2025 =
* Upgrades to TCPDI parser for better handling of external (URL) links
* Remove unused fonts from package to save dolphins

Older changes are found <a href="https://plugins.svn.wordpress.org/waterwoo-pdf/trunk/changelog.txt">in the changelog.txt file in the plugin directory.</a>