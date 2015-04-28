Evidence Hub 
====
Contributors: mhawksey, nfreear  
Tags: custom post type, evidence, oerhub   
Requires at least: 3.0.1  
Tested up to: 3.7.1  
Stable tag: 0.1    
License: GPLv2 or later  
License URI: http://www.gnu.org/licenses/gpl-2.0.html  

*Plugin to capture and visualise evidence around a set of hypotheses.*

Description
----

This plugin was developed to gather and publishes evidence about the impact of open educational resources (OER). It is maintained by the [OER Research Hub project](http://oerresearchhub.org/). Here's a [Site Demo](http://sites.hawksey.info/oerhub/)

Build on [wp-plugin-template by Francis Yaconiello](https://github.com/fyaconiello/wp_plugin_template) and influenced by [Conferencer by mattdeclaire and
briankwa](http://wordpress.org/plugins/conferencer/)

The plugin has a couple of shortcodes:  
* `[evidence_map]` - Overview of hub data and evidence flow  
* `[evidence_geomap]` - Plot of all evidence and projects  
* `[hypothesis_bar]` - Displays all the hypotheses and a summary of polarity 
* `[bookmarklet]` - Displays bookmarklet link 
  * Options: url - string submit page
  *          text - string button text
* `[evidence_entry]`
  * Options: do_cache - boolean to disable cache option default: *false*
* `[evidence_geomap]`	
* `[evidence_map]	`
* `[evidence_meta]`	
  * Options: location - header|footer|false
  *          header_terms - comma seperated list of fields to display
  *          footer_terms -  comma seperated list of fields to display
* `[evidence_summary]`	
* `[geomap]`
  * Options: type - string comma list of types to map
* `[get_post_meta]`	
  * Options: post_id - hypothesis id (defults to current post)
  *          meta_key - string of meta key value to get
* `[get_evidence_tagged]`	
* `[hypothesis_archive]`	
* `[hypothesis_balance]`	
* `[hypothesis_bar]`
* `[hypothesis_breakdown]`
* `[hypothesis_geosummary]`	
* `[evidence_ratings]`
* `[hypothesis_sankey]`	
* `[hypothesis_summary]`	
* `[policy_geomap]`
* `[policy_meta]`
* `[project_meta]`		
* `[survey_explorer]`
* `[api_demo]`

Libraries included
----

**Pronamic Google Maps**  
Contributors: pronamic, remcotolsma   
Donate link: http://pronamic.eu/donate/?for=wp-plugin-pronamic-google-maps&source=wp-plugin-readme-txt  
Requires at least: 3.0  
Tested up to: 3.4.1  
Stable tag: 2.2.9  
License: GPLv2 or later  
http://wordpress.org/plugins/pronamic-google-maps/  

**JSON API**  
Contributors: dphiffer  
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=DH4MEG99JR2WE  
Requires at least: 2.8  
Tested up to: 3.5.2  
Stable tag: 1.1.1  
http://wordpress.org/plugins/json-api/  

**WP-PostRatings**
Plugin URI: http://lesterchan.net/portfolio/programming/php/
Description: Adds an AJAX rating system for your WordPress blog's post/page.
Version: 1.78
Author: Lester 'GaMerZ' Chan
Author URI: http://lesterchan.net
Text Domain: wp-postratings

**Custom Headers and Footers**
Plugin URI: http://www.poradnik-webmastera.com/projekty/custom_headers_and_footers/
Description: This plugin adds custom header and footer for main page content.
Author: Daniel Frużyński
Version: 1.2
Author URI: http://www.poradnik-webmastera.com/
Text Domain: custom-headers-and-footers
License: GPL2

**Cookie Notice**
Version: 1.2.17
Author: dFactory
Author URI: http://www.dfactory.eu/
Plugin URI: http://www.dfactory.eu/plugins/cookie-notice/
License: MIT License
License URI: http://opensource.org/licenses/MIT
Text Domain: cookie-notice
Domain Path: /languages

**Facetious**  
Contributors: codeforthepeople, johnbillion, s1m0nd, simonwheatley  
Requires at least: 3.4  
Tested up to: 3.5  
Stable tag: 1.1.4 
License: GPL v2 or later  
http://wordpress.org/plugins/facetious/  

**Map (d3/SVG)**  
@author    		Timo Grossenbacher  
@copyright		CC BY-NC-SA 2.0 2013  
@license		MIT License (http://www.opensource.org/licenses/mit-license.php)  
http://labs.timogrossenbacher.ch/worldoil
Source: https://github.com/wnstnsmth/worldoil 

**Other libraries**   
+ d3JS (including sankey)
+ LeafletJS (including OverlappingMarkerSpiderfier)
+ BigScreen


Installation
----

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

Changelog
----

* **0.1** - Initial

