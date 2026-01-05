<p align="right"><a href="README-de.md">Deutsch</a> &nbsp; <a href="README.md">Englisch</a></p>

# Addtopage

Füge CSS- oder JavaScript-Dateien oder Metadaten in [Yellow](https://datenstrom.se/de/yellow/)-Webseiten ein.

<p align="center"><img src="ILLUSTRATION-addtopage.png" alt="Yellow-Addtopage"></p>

## Wie installiere ich die Erweiterung?

[Downloade diese ZIP-Datei](https://github.com/kgitthoene/yellow-addtopage/archive/refs/heads/main.zip) und kopiere sie in dieses Verziehnis auf dem Server: `system/extensions` [Lerne mehr über Erweiterungen](https://datenstrom.se/de/yellow/extensions/).

Beispielhafte Installation:

```
cd system/extensions
wget https://github.com/kgitthoene/yellow-addtopage/archive/refs/heads/main.zip -O yellow-addtopage-main.zip
```

## Warum es diese Erweiterung gibt

[Yellows](https://datenstrom.se/de/) Credo ist: „Benutze Technologie mit weniger Funktionen.“
Das ist großartig und ich mag es!
Aber was, wenn ein Hauch von Lakritz fehlt, um das Ganze schmackhafter zu machen?
Das nennt man dann Yellow-Erweiterung … 🍎

Der Yellow Weg um [JavaScript](https://de.wikipedia.org/wiki/JavaScript) in seine Seiten einzufügen, ist es eine Datei `<thema>.js` im Themenverzeichnis `system/themes` hinzuzufügen.
Siehe: [JavaScript anpassen](https://datenstrom.se/de/yellow/help/how-to-customise-a-theme#javascript-anpassen)

So weit, so gut.
Es sei denn man möchte dieses Script nicht in alle Seiten einfügen.
Oder man hat mehrere unterschiedliche Script-Dateien mit sprechenden Namen, die man wieder erkennen möchte.
Dasselbe gilt für CSS-Dateien.

Diese Erweiterung erlaubt es JS- und CSS-Dateinen speziell in bestimmte Seiten einzufügen.
Oder eben auch in alle.
Sie behalten dabei ihre Originalnamen oder werden direkt in HTML eingebettet.

Zusätzlich kann man mit dieser Erweiterung Meta-Informationen der jeweiligen Seite, des Systems oder der installierten Erweiterungen als JS-Daten in HTML einbetten und sie in JS-Programmen nutzen.
Das ist der Weg um JS bei Yellow unter die Haube schauen zu lassen.

## Wie man den Webseiten Sachen hinzufügt

Das erste was man kennen sollte ist die [Verzeichnisstruktur von Yellow](https://datenstrom.se/de/yellow/help/api-for-developers#verzeichnisstruktur).
Hier sueht man den Teil, der für uns interessant ist.

```
├── content               = Webseiten
├── media                 = Medien
│   └── downloads
└── system                = System-Dateien
    ├── extensions
    └── themes
```

In `media/downloads` (und selbstverständlich dessen Unterverzeichnissen) kopieren wir alle JS- und CSS-Dateien, die wir in Seiten laden möchten.
Es sei denn man ändert den Wert von `CoreDownloadLocation` im der Hauptkonfigurationsdatei  `system/extensions/yellow-system.ini`\
Yellows Vorgabewert für `CoreDownloadLocation` ist `/media/downloads`

### Die Anweisungen um Sachen hinzuzufügen

Jede Anweisung besteht aus drei Teilen, die durch Leerzeichen von einander getrannt sind.
Daher kann man leider keine Verzeichnisse oder Dateinamen verwenden, die Leerzeichen enthalten.

**`Typ` `Datei` `Optionen`**

<br/>

| `Typ` | Bedeutung |
| --- | --- |
| `JS` | Füge eine JavaScript-Datei ein. |
| `CSS` | Füge eine CSS-Datei ein.. |
| `PAGE` | Füge Metadaten über die aktuelle Seite als JS-Code ein. |
| `SYSTEM` | Füge Metadaten über das System als JS-Code ein. |
| `EXTENSIONS` | Füge Metadaten über die installierten Erweiterungen als JS-Code ein. |

<br/>

**`Datei`** – Name der JS- oder CSS-Datei in `media/downloads`\
Falls die `Datei` gleich `my-mighty-script.js` ist, dann sollte sie hier stehen: `media/downloads/my-mighty-script.js`

Für das Hinzufügen von Metadaten ist die Option `Datei` bedeutungslos.
Sie zu setzen ist überflüssig, es sei denn man benutzt gleichzeitig `Optionen`, dann sollte `Datei` auf `-` (Bindestrich) gesetzt werden.

<br/>

| `Optionen` | Bedeutung |
| --- | --- |
| `footer` | Fügt die Datei oder Metadaten in den Fuß der HTML-Ausgabe ein. Wenn `footer` nicht angegeben wird, dann wird in den Kopf der HTML-Ausgabe eingefügt. |
| `inline` | Dies wird immer für Metadaten benutzt, sprich Metadaten werden immer in die HTML-Ausgabe eingebettet. Daher kann man die Angabe für Metadaten weglassen. In Kobimation mit JS- oder CSS-Dateien, bedeutet dies, dass die Datei gelesen wird und dann in die HTML-Ausgabe eingebettet wird. |
| `debug` | Fügt ein paar Kommentare zur HTML-Ausgabe hinzu. |

### Wohin schreibe ich diese Anweisungen?

![#c5f015](https://placehold.co/15x10/c5f015/c5f015.png) **Seite:** Füge eine Abkürzung (en: shortcut) mit Parametern (Anweisung) in eine Seite ein.

**Beispiel:** `[addtopage JS my-mighty-script.js footer]`

Dies fügt das angegebene Script in dem Fuß dieser Seite ein.
Wenn man mehrere Dateien oder Metadaten einfügen möche, benutzt man mehrere Abkürzungen.

![#c5f015](https://placehold.co/16x10/c5f015/c5f015.png) **Seiten Einstellungen:** Füge eine oder mehrere Anweisungen in den [Seiten Einstellungen](https://datenstrom.se/de/yellow/help/how-to-change-the-system#seiteneinstellungen) hinzu.
Die Seiten-Einstellungen sind im Kopf des Markdown-Seitenquelltextes.

**Beispiel:**

```
---
Title: Beispielseite
Addtopage: JS my-mighty-script.js footer
---
```

Macht dasselbe, wie die die Abkürzung zuvor.

Wenn man mehrere Dateien oder Metadaten der Seite in den Seiten-Einstellungen hinzufügen möchte, dann sidn diese durch `|` zu trennen.

```
---
Title: Beispielseite
Addtopage: CSS my-important-style.css | JS my-mighty-script.js footer
---
```

Dies fügt `my-important-style.css` in den Seiten-Kopf und `my-mighty-script.js` in den Seiten-Fuß ein..

![#c5f015](https://placehold.co/16x10/c5f015/c5f015.png) **Thema:** Füge ein oder mehrere Anweisungen in die Datei `<thema>.addtopage` in `system/themes` ein.

**Beispiel:**

Inhalt von `system/themes/stockholm.addtopage`, falls das aktuelle Thema `stockholm` ist:

```
CSS my-important-style.css
JS my-mighty-script.js footer
```

Dies fügt `my-important-style.css` im Seiten-Kopf und `my-mighty-script.js` im Seiten-Fuß zu **<ins>jeder Seite von Yellow hinzu!</ins>**

![#f03c15](https://placehold.co/16x10/f03c15/f03c15.png) **Wichtig!** Seiten-Abkürzungen und Seiten-Einstellungen beeinflussen nur eine spezifische Seite. Themen-Anweisung wirken auf alle Web-Seiten.

### Wie nutzt man die hinzugefügten Metadaten?

Mit der Abkürzung `[addtopage PAGE]` fügt man der aktuellen Seite ihre Metadaten als JS-Datenstruktur hinzu.

HTML-Ausgabe (Beispiel!):


```
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
```

Benutze folgen den JS-Code um auf die Daten zuzugreifen:

```
const Page = globalThis[Symbol.for('Yellow-Page')];
console.log("Page.title = " + Page.title);
```

## Einstellungen

Die folgende Einstellung kann in `system/extensions/yellow-system.ini` gesetzt werden:

`AddToPageDebugMode`: Schalte Logging aus / an. Dies ist: `0`, `1`

0 = Logging aus. 1 = Logging an.

Vorgabewert: `AddToPageDebugMode: 0`

Logging-Datei: `system/extensions/yellow-website.log`

## Danksagung

Diese Erweiterung wurde durch [yellow-gallery](https://github.com/annaesvensson/yellow-gallery) von [Anna Svensson](https://github.com/annaesvensson) inspiriert.\
Das Beispiel benutzt: [Darkmode.Js](https://darkmodejs.learn.uno/) und [zepto.js](https://zeptojs.com/).\
Danke für die gute Arbeit.

## Entwickler

[Kai Thoene](https://github.com/kgitthoene)
