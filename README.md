# Twocents_XH – User Manual

Twocents_XH allows your visitors to add their two cents. ;) You can use it
as a general commenting possibility on multiple pages, or as a guestbook on
a single page, or both, and it can be used as comments plugin for
[Realblog_XH](https://github.com/cmb69/realblog_xh).
As markup language a minimal subset of HTML is optionally available,
which can be entered with a simple WYSIWYG editor.

Optionally you can enable comment moderation (i.e. comments will not be
published automatically), be notified about new comments via email, and you
can add spam prevention by using a
[conforming CAPTCHA plugin](https://wiki.cmsimple-xh.org/archiv/doku.php/captcha_plugins)
and a simple bad-word list.

- [Requirements](#requirements)
- [Download](#download)
- [Installation](#installation)
- [Settings](#settings)
- [Usage](#usage)
  - [Adminstration](#administration)
- [Limitations](#limitations)
- [Troubleshooting](#troubleshooting)
- [License](#license)
- [Credits](#credits)

## Requirements

Twocents_XH is a plugin for [CMSimple_XH](https://www.cmsimple-xh.org/).
It requires CMSimple_XH ≥ 1.7.0, and PHP ≥ 5.4.0 with the JSON
extension installed.

## Download

The [lastest release](https://github.com/cmb69/twocents_xh/releases/latest)
is available for download on Github.

## Installation

The installation is done as with many other CMSimple_XH plugins. See the
[CMSimple_XH Wiki](https://wiki.cmsimple-xh.org/?for-users/working-with-the-cms/plugins)
for further details.

1. **Backup the data on your server.**
1. Unzip the distribution on your computer.
1. Upload the whole directory `twocents/` to your server into the
   `plugins/` directory of CMSimple_XH.

1. Set write permissions for the subdirectories `cache/`,
   `css/`, `config/` and `languages/`.
1. Navigate to `Plugins` → `Twocents` and check if all requirements are
   fulfilled.

## Settings

The configuration of the plugin is done as with many other CMSimple_XH plugins
in back-end of the Website. Select `Plugins` → `Twocents`.

You can change the default settings of Twocents_XH under `Config`.
Hints for the options will be displayed when hovering over the help icon
with your mouse.

Localization is done under `Language`. You can translate the character
strings to your own language (if there is no appropriate language file
available), or customize them according to your needs.

The look of Twocents_XH can be customized under `Stylesheet`.

## Usage

To place a comment facility on a page, write:

    {{{twocents('%TOPICNAME%')}}}

`%TOPICNAME%` can be an arbitrary name, but it may contain only
the letters `a`-`z`, the digits `0`-`9` and hyphens (`-`).
The topicname is not necessarily related to the page heading;
in fact it is just the name of a file that is stored in the subdirectory
`twocents/` of the `content/` folder of CMSimple_XH.
If you like you can display the comments for the same topic on different pages.

Some examples:

    {{{twocents('guestbook')}}}
    {{{twocents('article-1')}}}

To mark a certain topic as read-only, so that only the administrator is able
to add comments, pass `true` as second argument to the function, for instance:

    {{{twocents('archived', true}}}

Note that there must be at most one call of `twocents` on a single page.

You can configure the very simplistic spam protection in the language
settings under `Spam` → `Words` which a list of comma separated
words. If any of these words are contained in the message (the actual case
of the letters doesn't matter), the comment will automatically be hidden.

### Administration

The administration of the comments happens on the pages where the comments
are displayed. When you are logged in as administrator, you will see buttons to
edit, hide/show and delete each comment.

In the plugin administration area you can convert existing comments from
and to HTML, respectively, depending on the setting of the configuration option
`Comments` → `Markup`. Note that it is best to do this only when
the site is in maintenance mode, and that you have to change this
configuration option after the conversion.

Furthermore there is the possibility to import comments from the
[Comments](https://ge-webdesign.de/cmsimpleplugins/?Eigene_Plugins___Comments)
and GBook plugins.
To use this, you have to copy the data files of the Comments or GBook plugin
into the data folder of Twocents_XH (which is the subfolder
`twocents/` of the `content/` folder of CMSimple_XH).
Note that some of the information will be ignored, e.g. the IP address
and the uploaded image, as well as the markup when Twocents_XH is in plain text mode.

## Limitations

Currently, posting comments from very old browsers such as IE 6 and IE 7
is not possible; the form submission will silently fail.

Twocents_XH uses HTML5 form validation features. These will be critized by
validators for HTML 4.01 and XHTML 1.0 templates. Other than that they cause
no harm; old browsers will simply ignore them, and contemporary browsers
will heed them nonetheless. It is recommended that your template uses the
so-called HTML 5 doctype declaration, anyway.

## Troubleshooting

Report bugs and ask for support either on
[Github](https://github.com/cmb69/twocents_xh/issues)
or in the [CMSimple_XH Forum](https://cmsimpleforum.com/).

## License

Twocents_XH is free software: you can redistribute it and/or modify it
under the terms of the GNU General Public License as published
by the Free Software Foundation, either version 3 of the License,
or (at your option) any later version.

Twocents_XH is distributed in the hope that it will be useful,
but without any warranty; without even the implied warranty of merchantibility
or fitness for a particular purpose.
See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Twocents_XH. If not, see https://www.gnu.org/licenses/.

Copyright © 2014-2023 Christoph M. Becker

## Credits

Twocents_XH uses [HTML Purifier](http://htmlpurifier.org/).
Many thanks for publishing this great tool under LGPL.

The plugin logo has been designed by [Alessandro Rei](http://www.mentalrey.it/).
Many thanks for publishing the icon under GPL.

Many thanks to the community at the
[CMSimple_XH Forum](http://www.cmsimpleforum.com/)
for tips, suggestions and testing.
Special thanks to *frase* and *lck* for testing and valuable feedback.

And last but not least many thanks to [Peter Harteg](http://www.harteg.dk/),
the “father” of CMSimple, and all developers of [CMSimple_XH](https://www.cmsimple-xh.org/)
without whom this amazing CMS would not exist.
