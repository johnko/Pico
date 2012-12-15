/*
Title: Welcome
Description: This description will go in the meta description tag
Robots: noindex,nofollow
Theme: default
*/

Welcome to Pico
===============

Congratulations you have successfully installed [Pico](http://pico.dev7studios.com). Pico is a stupidly simple, blazing fast, flat file CMS.

Creating Content
----------------

Pico is a flat file CMS, this means there is no administration backend and database to deal with. You simply create `.txt` files in the "content"
folder and that becomes a page. For example this file is called `index.txt` and is shown as the main landing page.

If you created folder within the content folder (e.g. `content/sub`) and put an `index.txt` inside it, you can access that folder at the URL
`http://yousite.com/sub`. If you want another page within the sub folder, simply create a text file with the corresponding name (e.g. `content/sub/page.txt`)
and will be able to access it from the URL `http://yousite.com/sub/page`. Below we've shown some examples of content locations and their corresponding URL's:

<table>
	<thead>
		<tr><th>Physical Location</th><th>URL</th></tr>
	</thead>
	<tbody>
		<tr><td>content/index.txt</td><td>/</td></tr>
		<tr><td>content/sub.txt</td><td>/sub</td></tr>
		<tr><td>content/sub/index.txt</td><td>/sub (same as above)</td></tr>
		<tr><td>content/sub/page.txt</td><td>/sub/page</td></tr>
		<tr><td>content/a/very/long/url.txt</td><td>/a/very/long/url</td></tr>
	</tbody>
</table>

If a file cannot be found, the file `content/404.txt` will be shown.

Text File Markup
----------------

Text files are marked up using [Markdown](http://daringfireball.net/projects/markdown/syntax). This fork is using [PHP Markdown Extra](http://michelf.ca/projects/php-markdown/extra/). They can also contain regular HTML.

At the top of text files you can place a block comment and specify certain attributes of the page. For example:

	/ *
	Title: Welcome
	Description: This description will go in the meta description tag
	Robots: noindex,nofollow
	Theme: default
	*/

These values will be contained in the `{{ meta }}` variable in themes (see below).

There are also certain variables that you can use in your text files:

* &#37;base_url&#37; - The URL to your Pico site

#### Custom variables

Custom variables can be referenced in your markdown like:

* &#37;custom_setting&#37;

which produces:

%custom_setting%

Themes
------

You can create themes for your Pico installation and in the "themes" folder. Check out the default theme for an example of a theme. Pico uses
[Twig](http://twig.sensiolabs.org/documentation) for it's templating engine. You can select your theme by changing the meta `Theme:`-tag or by setting the `$config['theme']` variable
in config.php to your theme folder.

All themes must include an `index.html` file to define the HTML structure of the theme. Below are the Twig variables that are available to use in your theme:

* `{{ config }}` - Contains the values you set in config.php (e.g. `{{ config.theme }}` = "default")
* `{{ base_dir }}` - The path to your Pico root directory
* `{{ base_url }}` - The URL to your Pico site
* `{{ theme_dir }}` - The path to the Pico active theme directory
* `{{ theme_url }}` - The URL to the Pico active theme directory
* `{{ site_title }}` - Shortcut to the site title (defined in config.php)
* `{{ meta }}` - Contains the meta values from the current page (e.g. `{{ meta.title }}`, `{{ meta.description }}`, `{{ meta.robots }}`, `{{meta.theme}}`)
* `{{ content }}` - The content of the current page (after it has been processed through Markdown)
* `{{ navigation }}` - The automatically generated navigation of the content folder

Config
------

You can override the default Pico settings (and add your own custom settings) by editing config.php in the root Pico directory. The config.php file
list all of the settings and their defaults. To override a setting simply uncomment it in config.php and set your custom value.
