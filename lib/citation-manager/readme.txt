=== Citation Manager ===
Contributors: mikegogulski  
Donate link: http://www.nostate.com/support-nostatecom/  
Tags: citation, shortcode, meta  
Requires at least: 2.9  
Tested up to: 3.0.3 
Stable tag: trunk  

Citation Manager - Management and display of external, manual citations to
WordPress content


== Description ==

Allows for tracking and display of external citations to WordPress content.

While trackbacks and pingbacks are fine for displaying references from other
websites which support them, what do you do when, say, a print newspaper,
book or magazine makes reference to your content? Citation Manager provides
a solution.

== Long Description ==

= At a Glance =

* Adds a form to post and page screens to manage external citations
* Displays an unordered list of citations per post/page, or nothing if there
  are no citations
* Provides shortcodes for summarizing citation data

= Usage =

* On the page/post edit screen, press "Add citation".
* Fill in *at least* the Publication or Title fields and save/update.
* On pages/posts with citations, Citation Manager will output them after the
  content.
* If a URL element is provided, it will be applied to the Title if present or
  to the Publication if not.
* Press "Delete citation" and save/update to remove a citation.
* Use Settings->Citation Manager to alter the text/HTML to come before and
  after the citation list.
* You can also configure the presence of the `target="_blank"` attribute in
  links to external sites when citations are listed, causing those links to
  open in a new browser window. 

= Shortcodes =

* `[citation-count-total]` outputs an integer indicating the total number of
  citations to all content on the site
* `[citation-count]` outputs an integer indicating the total number of
  citations to the present page or post
* `[citation-dump]` outputs a nested unordered list of page/post titles and
  citations to them


== Changelog ==
= 0.9.6 =
* Fixed citation-dump output escaping, ul/li nesting

= 0.9.5 =
* Fixed citation creation bug introduced in 0.9.4.

= 0.9.4 =

* Added option for `target="_blank"` attribute in external links
* Added option to select the sort order for per-post citation display and
  `[citation-dump]`
* Completely empty citations will no longer be saved

= 0.9.3 =

* Changed sort order of per-post citation display and `[citation-dump]` to
  descending by post/meta ID

= 0.9.2 =

* Changed handling of publication and title to allow em etc. tags

= 0.9.1 =

* Fixed HTML escaping for citation listing intro and outro text

= 0.9.0 =

* Initial release


== Installation ==

1. Download the *Citation Manager* plugin .zip file.
1. Unzip the archive.
1. Upload the `citation-manager` folder to `wp-content/plugins/`.
1. Go to the *Plugins* tab.
1. Activate *Citation Manager*.
1. Edit your settings, if necessary.

== Support ==

For support questions, bug reports, etc., please visit the plugin's
[support page](http://www.nostate.com/3782/citation-manager-for-wordpress-plugin/)

== License ==

Citation Manager is released into the public domain via the [Unlicense](http://unlicense.org/).
