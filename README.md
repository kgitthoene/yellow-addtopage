<p align="right"><a href="README.md">English</a> &nbsp; <a href="README-de.md">Deutsch</a></p>

# Addtopage

Add CSS, JavaScript files or meta data to [Yellow](https://datenstrom.se/yellow/) web pages.

<p align="center"><img src="addtopage-machine.png" alt="Yellow-Addtopage"></p>

## How to install an extension

[Download ZIP file](https://github.com/kgitthoene/yellow-addtopage/archive/refs/heads/main.zip) and copy it file into your `system/extensions` folder. [Learn more about extensions](https://datenstrom.se/yellow/extensions/).

## Overall Example

Inject files and meta data to all of your pages. Place a file `<theme>.addtopage`  in the themes directory `system/themes`

Say your theme is `stockholm`, then place a file **`stockholm.addtopage`** in the themes directory.

Place additional JavaScript and CSS files into the downloads folder `media/downloads`

```
├── media
|   └── downloads
|       └── js
|           └── darkmode
|               └── darkmode.js    = file to inject into all pages
└── system
    └── themes
        └── stockholm.addtopage    = contains Addtopage instructions
```

If `stockholm.addtopage` is:

```
PAGE - footer
JS js/darkmode/darkmode.js footer
```

Then the page meta data is set on all pages (JavaScript code), and the file  `js/darkmode/darkmode.js` is injected to all pages.

For details see the next sections.

## How to add CSS, JavaScript files to your web page(s)

There are three ways to add files to your web pages.

Method **(1)** and **(2)** affects one single page. Method **(3)** affects all pages.

### (1) Create a `[addtopage]` shortcut

For CSS and JavaScript the following arguments are available, but the last argument ist optional:

`[addtopage Type File Options]`

`Type` = File type: `CSS`, `STYLE`, `JAVASCRIPT` or `JS`

`File` = File to import: File name or file path with or without leading `/`

`Options` = Multiple, single or none option key words: `footer`, `inline`, `debug`  (separated by `:`    e.g.   `footer:inline`  )

The files are placed in `media/downloads`

[^1]: Unless you change `CoreDownloadLocation` in the main configuration file `system/extensions/yellow-system.ini`

​     See: [Yellow Folder Structure](https://datenstrom.se/yellow/help/api-for-developers#folder-structure)

```
├── content               = content files
├── media                 = media files
│   └── downloads         = files for download / injecting
└── system                = system files
```

The types `CSS` and `STYLE` are synonymous. Also the types `JS` and `JAVASCRIPT`.

#### Example for (1)

Add to your page content (markup): **`[addtopage JS js/darkmode/darkmode.js footer]`**

```
├── media
    └── downloads
        └── js
            └── darkmode
                └── darkmode.js    = file to insert into page
```

The file will be inserted in the footer of the page (resulting HTML code). Example:

```
...
<script type="text/javascript" src="/media/downloads/js/darkmode/darkmode.js"></script>
</body>
</html>
```

If you omit the `footer` option, the file will be added to the page header. Example:

```
...
<meta name="generator" content="Datenstrom Yellow" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<script type="text/javascript" src="/media/downloads/js/darkmode/darkmode.js"></script>
...
```

### (2) Add Addtopage to the page settings

See: [How to change the system / Chapter: Page Settings](https://datenstrom.se/yellow/help/how-to-change-the-system#page-settings)

For CSS and JavaScript the following arguments are available, but the last argument is optional:

Add to page setting: `Addtopage: Type File Options`

#### Example for (2)

```
---
Title: Example page
Addtopage: JS js/darkmode/darkmode.js footer
---

This is an example page setting with file injection.
```

##### If you want to add multiple files to the page settings, separate them by `|`

Example:

```
---
Title: Example page
Addtopage: CSS css/injected-style.css | JS js/darkmode/darkmode.js footer
---

This is an example page setting with multiple file or meta data injection.
```

Folder structure for this example:

```
├── media
    └── downloads
        ├── css
        │   └── injected-style.css    = 1st file to inject into page
        └── js
            └── darkmode
                └── darkmode.js       = 2nd file to inject into page
```

### (3) All pages: Inject CSS and JavaScript

Place a file `<theme>.addtopage`  in the themes directory:

Say your theme is `stockholm`, then place a file **`stockholm.addtopage`** in the themes directory:

```
└── system                        = system files
    └── themes                    = theme files
        └── stockholm.addtopage   = contains Addtopage instructions
```

If `stockholm.addtopage` is:

```
CSS css/injected-style.css
JS js/darkmode/darkmode.js footer
```

Then the files `css/injected-style.css` and `js/darkmode/darkmode.js` are injected to all pages.

Folder structure for this example:

```├── media
├── media
│   └── downloads
│       ├── css
│       │   └── injected-style.css    = 1st file to inject to page
│       └── js
│           └── darkmode
│               └── darkmode.js       = 2nd file to inject to page
└── system
    └── themes
        └── stockholm.addtopage   	  = contains Addtopage instructions
```

## How to add meta data to your web page(s)

If you want to look Yellow under the hood by JavaScript, you may add some meta data as JavaScript code to your web pages.

There are three ways to add meta data to your web pages. You use the same anchors to place the instructions to inject meta data like style or script files.

Method **(1)** and **(2)** affects a single page. Method **(3)** affects all pages.

#### **(1)** Shortcode: `[addtopage Type Dummy Options]` 

`Type` = Meta data type: `PAGE`, `SYSTEM`

`Dummy` = File file argument: `-`    Must use this, if you set options. No options, no dummy needed.

`Options` = Multiple, single or none option key words: `footer`, `debug`  (separated by `:`    e.g.   `footer:debug`  )

#### **(2)** Page settings:

```
---
Title: Example page
Addtopage: Type Dummy Options
---
```

If you want to add multiple files or meta data to the page settings, separate them by `|` 

```
---
Title: Example page
Addtopage: Type Dummy Options | Type Dummy Options
---
```

#### **(3)** All pages: Add file `<theme>.addtopage`  in the themes directory

```
Type Dummy Options
```

Say your theme is `stockholm`: place **`stockholm.addtopage`** in `system/themes`

```
PAGE - footer
```

Results in:

```
...
<script type="text/javascript">
globalThis[Symbol["for"]('Yellow-Page')] = {
    "title": "Digamma",
    "language": "en",
    "modified": "2025-12-29 23:34:27",
    "sitename": "Digamma",
    "author": "Administrator",
    "layout": "default",
    "theme": "stockholm",
    "parser": "markdown",
    "status": "public",
    "description": "Halle (Westphalia) Art Gallery",
    "image": "site-image.jpg",
    "titlecontent": "Digamma",
    "titlenavigation": "Digamma",
    "titleheader": "Digamma",
    "editpageurl": "http://localhost/edit/"
};
</script>
</body>
</html>
```

Access this data in JavaScript:

```
const Page = globalThis[Symbol.for('Yellow-Page')];
console.log("PAGE=" + Page.title);
```

#### Example `Type` = `SYSTEM` in a shortcode

```
[addtopage SYSTEM - footer]
```

Results in:

```
...
<script type="text/javascript">
globalThis[Symbol["for"]('Yellow-System')] = {
    "sitename": "Digamma",
    "author": "Administrator",
    "email": "nobody@administrator.unknown",
    "language": "en",
    "layout": "default",
    "theme": "stockholm",
    "parser": "markdown",
    "status": "public",
...
};
</script>
</body>
</html>
```

Access this data in JavaScript:

```
const System = globalThis[Symbol.for('Yellow-System')];
console.log("SITENAME=" + System.sitename);
```

## Settings

The following setting can be configured in file `system/extensions/yellow-system.ini`:

`AddToPageDebugMode`: Switch logging off / on. E.g. `0`, `1` 

0 = Logging off. 1 = Logging on.

Default: `AddToPageDebugMode: 0`

Log file location: `system/extensions/yellow-website.log`

## Acknowledgements

This extension is inspired by [yellow-gallery](https://github.com/annaesvensson/yellow-gallery) by [Anna Svensson]([https://github.com/annaesvensson). Thank you for the good work.

## Developer

[Kai Thoene](https://github.com/kgitthoene)

