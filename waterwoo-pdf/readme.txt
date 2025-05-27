=== PDF Ink Lite - Stamp PDFs with Customer Data ===
Contributors: littlepackage
Donate link: https://paypal.me/littlepackage
Tags: pdf, password, watermark, woocommerce, stamp
Requires at least: 4.9
Tested up to: 6.8
Requires PHP: 7.2
Stable tag: 3.7.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

PDF Ink Lite (FKA WaterWoo) allows WooCommerce, Download Monitor & Easy Digital Downloads site administrators to apply a custom watermark & password to a simple PDF upon sale.

== Description ==
PDF Ink Lite is a free plugin that can add a watermark to every page of your sold PDF file(s). It can also password and permissions protect your PDF file(s). The watermark is customizable with font face, font color, font size, placement, and text.

This plugin watermarks PDF products when downloaded using WooCommerce download links, and works similarly for Download Monitor and Easy Digital Downloads.

Since the watermark is added when the download button is clicked (either on the customer's order confirmation page or email, or account page), the watermark can include customer-specific data such as the customer's first name, last name, and email.

Upon purchase download link, this plugin uses the open source TCPDI and TCPDF libraries to customize your PDF. This process isn't fool-proof, but works well in many cases. You may encounter problems if your PDF is malformed (bad PDF syntax), encrypted, web-optimized, linearized, or if your server cannot handle the memory load of PDF processing.

[Check out PDF Ink, the full version of PDF Ink Lite](https://pdfink.com "PDF Ink") for better PDF coverage and more options.

= Features: =

* Watermark only designated PDF downloads (as specified by you), or *all* PDF downloads from your site
* Files do not need to be in a specific directory
* Customizable watermark placement can be moved all over the page, allowing for different paper sizes (such as letter, A4, legal, etc)
* Watermark is applied to **all** pages of **every** PDF purchased ([upgrade for more control](https://pdfink.com "Upgrade to the full version"))
* Watermarks upon click of customer's order confirmation page link, email order confirmation link, or My Account page download links
* Dynamic customer data inputs (customer first name, last name, email, order paid date, and phone)
* Choice of font face, color, size and placement (horizontal line of text anywhere on the page)
* Compatible up to PHP 8.3

PDF Ink Lite is the only watermarker for WordPress which includes necessary libraries (so you don't have to ask your host to load them), is compatible with PHP 7+, and watermarks newer versions of PDFs (not just older versions).

= Upgraded (paid) PDF Ink version features: =

The free version is enough for some people, but our all-in-one plugin [PDF Ink](https://pdfink.com "PDF Ink") offers these helpful extra features:

* Higher level PDF protections with AES encryption and extended file protection settings
* Additional dynamic customer data input (business name, address, order number, product name, quantity of product purchased), and filter hooks for adding even more
* Test watermark and/or manually watermark a file on the fly, from the admin panel
* Keep original file metadata
* Open ZIP files and mark PDF files inside the archive
* Begin watermark on selected page of PDF document (to avoid watermarking a cover page, for example), and/or select end page
* Watermark every page, odd pages, even pages, or selected pages
* Watermark all PDF files with same settings OR set individual watermarks/passwords per product or even per product variation
* Unlimited rotatable watermark locations on one page, anywhere on the page
* Semi-opaque (transparent) watermarks - hide your watermarks completely if desired
* RTL (right to left) watermarking
* Use of some HTML tags to style your output, including text-align CSS styling (right, center, left is default), links (&lt;a&gt;), bold (&lt;strong&gt;), italic (&lt;em&gt;)...
* Additional text formatting options, such as font color and style (bold, italics) using HTML
* Line-wrapping, forced breaks with &lt;p&gt; and &lt;br /&gt; tags
* Preserves external embedded PDF links despite watermarking; internal links are not preserved (use the [PDF Ink plugin with SetaPDF Stamper](https://pdfink.com) for this feature)
* Upload and use your own font for stamping. Also, hooks to further customize font use
* Filter hooks to add 1D and 2D barcodes (including QR codes)

[Check out the full-featured version of this plugin](https://pdfink.com "PDF Ink")!

== Installation ==

= Minimum Requirements =

* WordPress 4.9 or greater (recommend 6.6)
* WooCommerce 4.0 and newer (recommend 8.2)
* PHP version 7.4 or greater (recommend at least 7.4 but < 8.5)
* PDFs version under 2.0

Please use the most recent version of all WordPress software - it's what we support!

= We recommend your host supports: =

* WordPress Memory limit of 64 MB or greater (usually <=512MB works fine)
* PHP max_execution_time up to 60 seconds (30 should be fine)
* If you have large PDF files and/or heavy download traffic, you may need to pay for beefier hosting with more CPUs. A shared hosting plan might not cut it.

= To install plugin =
1. Upload the entire "waterwoo-pdf" folder to the "/wp-content/plugins/" directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Visit WooCommerce->Settings->Watermark tab to set your plugin preferences.
4. **Please test your watermarking** by making mock purchases before going live to make sure it works and looks great!
5.  Note: for this to work you might need to have pretty URLs enabled from the WP settings. Otherwise a 404 error might be thrown.

= To remove plugin: =

1. Deactivate plugin through the 'Plugins' menu in WordPress
2. Delete plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Something is wrong =
1. Is WooCommerce/Download Monitor/Easy Digital Downloads installed, and do you have a PDF product in your shop to watermark?
2. Update WordPress, and all plugins including this plugin to the most recent versions. We recommend you set PHP max 8.4 because that is what TCPDF can handle. Downgrade your PHP if needed.
2. Have you checked the PDF Ink Lite settings checkbox to enable watermarking?
3. Have you entered your PDF file names correctly in the second field if you've entered any at all? This field is case-sensitve.
4. Try again with a different font.
5. Is your PDF version 2.0? You'll want to downgrade your PDF or look into using [PDF Ink with SetaPDF-Stamper](https://pdfink.com "PDF Ink plugin") instead.
6. Is your Y fine-tuning adjustment off the page? Read more below under "Why does the watermark go off the page, create blank pages?".
7. If using WooCommerce, go to WooCommerce -> Settings -> Watermark -> Error/event logging, turn logging on. If using Easy Digital Downloads, go to Settings -> Misc -> Debug Mode and turn on logs. Run the program again, then look at the logs.
8. Check your WP debug logs (link to instructions below). If logs suggest your PDF is "goofy" or "template does not exist," try using Apple Preview application to resave your PDF by clicking "Export as PDF" in the menu. Preview might fix bad PDF syntax and allow your PDF to be processed for watermarking.
9. It may also help to increase your PHP time limit and memory limits if they are set low. Server limitations can stop this plugin from functioning well.

= Further things to try: =
1. Make sure your PDF downloads work WITHOUT PDF Ink Lite activated, to narrow the problem.
2. Try watermarking a different PDF (one you didn't create). If your PDF has goofy syntax (and many do - correct PDF syntax is "optional" for some PDF builders), this plugin will not be able to read it. Look into using [PDF Ink with SetaPDF-Stamper](https://pdfink.com "PDF Ink") instead.
3. Read through the [support forum](https://wordpress.org/support/plugin/waterwoo-pdf/). Tip: it has a search feature! Your answer is probably there by now since this plugin has been around over ten years.

Please do get in touch with your issues via the WordPress.org support forum before leaving negative feedback about this free plugin.

If requesting help using the WordPress.org support forum, please state which versions WordPress/(Woo|DLM|EDD)/PDF Ink Lite you are using, and what error messages if any you are seeing. You will find more detailed error messaging if you [turn on Wordpress debugging](https://wordpress.org/support/article/debugging-in-wordpress/). Screenshots and clear descriptions of the steps it takes to reproduce your problem are also very helpful. Please also make your support request TITLE descriptive. If the answer to your question can be found on this page, you might be waiting a while to hear from me, as I must prioritize support requests. Thanks for understanding.

If you get the message "Sorry, we were unable to prepare this file for download," please check your WP debug logs for more details.

**Do not use the WordPress.org support forum for help with the upgraded (paid) version** of PDF Ink - that is against WordPress.org rules. Conversely, do not email us for support. Use the Wordpress.org support channel.

= Where do I change PDF Ink Lite settings? =

You can find the PDF Ink settings page by clicking on the "Settings for XXX" link under the PDF Ink Lite plugin title on your WordPress plugins panel.

= My watermark isn’t English =

Try selecting the “Deja Vu,” “Furat,” or “M Sung” font in the plugin settings panel.

One reason watermarks might not show up is because the watermark contains special characters but you're using a font which doesn’t support those characters. If none of the included fonts are subsetted for your language characters, you will need to programmatically add fonts yourself or look into purchasing the full version of this plugin, which has many more built-in fonts and supports font uploads.

= How do I test my watermark? =
Maybe set your PDF to $0 (free) and "Privately Published". Or maybe create a coupon in your shop to allow 100% free purchases. Don't share this coupon code with anyone! Test your watermark by purchasing PDFs from your shop using the coupon. It's a bit more tedious. If you want easier on-the-fly testing, purchase the full version of this plugin.

= Why does the watermark go off the page, create blank pages? =
Your watermark text string is too big or long for the page, and goes off it! Try decreasing font size or using the Y fine tuners to move the watermark back onto the page. Try lowering your "y-axis" value. This number corresponds to how many *millimeters* you want the watermark moved down the page. For example, if your PDF page is 11 inches tall, your Y-axis setting should be a deal less than 279.4mm in order for a watermark to show. The built-in adjustments on the settings page ultimately allow for watermarking on all document sizes. You may need to edit your watermark if it is too verbose.

You can use a negative integer value for your Y-tuner and measure up from the bottom of the page. This is especially helpful if your PDF has variable sized pages.

= Where do the watermarked files go? =
They are generated with a unique name and stored in the same folder as your original WordPress/Woo product media upload (usually wp-content/uploads/year/month/file). The unique name includes the order number and a time stamp. If your end user complains of not being able to access their custom PDF for some reason (most often after their max number of downloads is exceeded), you can find it in that folder, right alongside your original.

If you are using Woo FORCED downloads, the plugin attempts to delete the watermarked files after being delivered. This isn't 100% reliable since it works on PHP shutdown. If you don't like attempted deletion, you can change it with the 'wwpdf_do_cleanup' filter hook (set it to FALSE). The paid version of this plugin has improved file handling/removal.

= Will PDF Ink Lite watermark images? =
PDF Ink is intended to watermark PDF (.pdf) files. If you are specifically looking to watermark image files (.jpg, .jpeg, .gif, .png, .etc), you may want to look into a plugin such as [Image Watermark](https://wordpress.org/plugins/image-watermark/ "Image Watermark Plugin").

= Does this work for ePub/Mobi files =
No, sorry. This plugin is just for PDF files.

= The plugin seems to break my PDF =
This plugin bridges e-commerce and the open-source PDF reading TCPDI and PDF writing TCPDF library. PDF Ink functions by parsing/reading your PDF into memory the best it can, then adding a watermark to the PDF syntax and outputting a revised file. Between the reading and output, certain features may be lost and other features (interactive PDF elements like internal links and fillable forms) will be lost. This is a limitation of the open-source third-party library used AND the wild-west nature of PDF syntax. It is not the fault of PDF Ink, which simply uses those 3rd party open-source libraries. Ultimately, PDF Ink Lite is best for simple, smaller-sized and well-formed PDFs. If you are serious about watermarking and/or encrypting complex PDF files, [purchase the very best plugin available for your PDFs, PDF Ink](https://pdfink.com "PDF Ink plugin") and combine that with better libraries to handle your PDF.

= Is there a fallback in case watermarking fails? =
Yes, you can serve the file untouched if watermarking fails, and avoid any error messages, by using the following filter code in your (child) theme functions.php file:

`add_filter( 'wwpdf_serve_unwatermarked_file', '__return_true' );`

If you do not know how to edit your functions.php file, you can use the Code Snippets plugin to easily add this code to your WP site frontend.

== Screenshots ==

1. Screenshot of the settings page, as a Woocommerce settings tab.

== Upgrade Notice ==

= 4.0 =
* If you have overwritten parts of this plugin or are using filter hooks, this could be a breaking update. In that case we recommend you take backups and work on a non-production server to navigate your upgrade

== Changelog ==

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

= 3.5.2 - 24 January 2025 =
* Testing with Woo 9.6
* Upgrade TCPDF to 6.8.0
* WaterWoo now upgrades to =PDF Ink=, a plugin which can work with ANY PDF with more marking/passwording features than ever. Woohoo!

= 3.5.1 - 3 December 2024 =
* Notice for people trying to mark PDFs version >= 2.0. Folks must use a different (paid) parser for this, or downgrade PDF version.
* Fix uninstall.php to remove newer settings (such as security settings) when plugin deleted.

= 3.5.0 - 18 October 2024 =
* Fork tcpdi_parser.php and add catch for presence of <> in getRawObject() method; modernize syntax and correct some logic - plugin will likely work with more PDFs now

= 3.4 - 10 October 2024 =
* Feature - add basic PDF passwording
* Feature - add basic debugging

= 3.3.8 - 10 October 2024 =
* Correct missing support links on plugins.php page
* Confirm compatiblity with PHP 8.3, WC 9.3

= 3.3.7 - 26 July 2024 =
* Update CTA links from web.little-package.com to www.little-package.com

= 3.3.6 - 26 July 2024 =
* Tweak - Update TCPDF to version 6.7.5 (with namespacing)
* Tweak - Declare compatibilty with WooCommerce `cart_checkout_blocks` (HPOS) feature
* Testing with WP 6.6, WC 9.1, PHP 8.3

= 3.3.5 - 27 Nov 2023 =
* Separate constructor and main do_watermark() method in class WWPDF_Watermark
* Remove CTA, which also removes need for all CSS/JS
* Update integration with WC settings API
* Testing with WC 8.3, PHP 8.2

= 3.3.4 - 14 Oct 2023 =
* Remove unused, auto-generated js/css files
* Update collaborators

= 3.3.3 - 28 Aug 2023 =
* Remove `register_activation_hook` which wasn't used anymore plus was attached to a hook where it wasn't fired anyway
* Tweak - improve exception handling and error feedback from external libraries (TCPDI/TCPDF) on failed downloads
* Compatibility testing
* Update POT file

= 3.3.2 - 28 July 2023 =
* Testing with WooCommerce v7.9
* PHP 8.2 compatibility tweak in lib/tcpdf/include/tcpdf_fonts.php
* Rework how compatibility is checked - before plugin is loaded
* Deprecate several hooks in the free version. While I love open source, I also need to be able to support myself. The paid (Premium) version includes these filter hooks. You can continue using them free but they will disappear at the next breaking update. Thanks for understanding.

= 3.3.1 - 28 June 2023 =
* Testing with WooCommerce v7.8

= 3.3 - 9 June 2023 =
* Use TCPDF Write() arguments to center watermark, not GetStringWidth(), add 'wwpdf_write_URL' and 'wwpdf_write_align' filter hooks to the TCPDF Write() method call

Older changes are found <a href="https://plugins.svn.wordpress.org/waterwoo-pdf/trunk/changelog.txt">in the changelog.txt file in the plugin directory.</a>
