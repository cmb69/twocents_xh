# Twocents_XH

Twocents_XH erlaubt Ihren Besuchern ihren Senf abzugeben (engl. "to put in
one's two cents"). ;) Sie können es als allgemeine Kommentarmöglichkeit auf
mehreren Seiten, oder als Gästebuch auf einer einzigen Seite, oder als
beides, sowie als Kommentar-Plugin für
[Realblog_XH](https://github.com/cmb69/realblog_xh) verwenden.
Als Auszeichnungssprache steht optional eine minimale Untermenge von HTML
zur Verfügung, die mit einem schlichten WYSIWYG-Editor eingegeben werden kann.

Optional können Sie die Kommentar-Moderation aktivieren (d.h. Kommentare
werden nicht automtisch veröffentlicht), sich über neue Kommentare per
E-Mail informieren lassen, und zur Spam-Prävention können Sie ein
[spezifikationsgerechtes CAPTCHA-Plugin](https://wiki.cmsimple-xh.org/archiv/doku.php/captcha_plugins)
und eine einfache Bad-Word-Liste nutzen.

- [Voraussetzungen](#voraussetzungen)
- [Download](#download)
- [Installation](#installation)
- [Einstellungen](#einstellungen)
- [Verwendung](#verwendung)
  - [Administration](#administration)
- [Einschränkungen](#einschränkungen)
- [Problembehebung](#problembehebung)
- [Lizenz](#lizenz)
- [Danksagung](#danksagung)

## Voraussetzungen

Twocents_XH ist ein Plugin für [CMSimple_XH](https://www.cmsimple-xh.org/de/).
Es benötigt CMSimple_XH ≥ 1.7.0 und PHP ≥ 7.1.0.
Twocents_XH benötigt weiterhin das [Plib_XH](https://github.com/cmb69/plib_xh) Plugin;
ist dieses noch nicht installiert (see *Einstellungen*→*Info*),
laden Sie das [aktuelle Release](https://github.com/cmb69/plib_xh/releases/latest)
herunter, und installieren Sie es.

## Download

Das [aktuelle Release](https://github.com/cmb69/twocents_xh/releases/latest)
kann von Github herunter geladen werden.

## Installation

The Installation erfolgt wie bei vielen anderen CMSimple_XH-Plugins auch.
Im [CMSimple_XH-Wiki](https://wiki.cmsimple-xh.org/de/?fuer-anwender/arbeiten-mit-dem-cms/plugins)
finden Sie weitere Informationen.

1. **Sichern Sie die Daten auf Ihrem Server.**
1. Entpacken Sie die ZIP-Datei auf Ihrem Computer.
1. Laden Sie das gesamte Verzeichnis `twocents/` auf Ihren Server in
   das `plugins/` Verzeichnis von CMSimple_XH hoch.
1. Vergeben Sie Schreibrechte für die Unterverzeichnisse `cache/`,
   `css/`, `config/` und `languages/`.
1. Navigieren Sie nach `Plugins` → `Twocents` und prüfen Sie, ob alle
   Voraussetzungen für den Betrieb erfüllt sind.

## Einstellungen

Die Konfiguration des Plugins erfolgt wie bei vielen anderen
CMSimple_XH-Plugins auch im Administrationsbereich der Website.
Wählen Sie `Plugins` → `Twocents`.

Sie können die Original-Einstellungen von Twocents_XH unter
`Konfiguration` ändern. Beim Überfahren der Hilfe-Icons mit der Maus
werden Hinweise zu den Einstellungen angezeigt.

Die Lokalisierung wird unter `Sprache` vorgenommen. Sie können die
Zeichenketten in Ihre eigene Sprache übersetzen (falls keine entsprechende
Sprachdatei zur Verfügung steht), oder sie entsprechend Ihren Anforderungen
anpassen.

Das Aussehen von Twocents_XH kann unter `Stylesheet` angepasst werden.

## Verwendung

Um eine Kommentargelegenheit auf einer Seite zu platzieren, schreiben Sie:

    {{{twocents('%THEMENNAME%')}}}

`%THEMENNAME%` kann ein beliebiger Name sein, aber er darf nur
die Buchstaben `a`-`z`, die Ziffern `0`-`9` und Minuszeichen (`-`) enthalten.
Der Themenname steht nicht notwendigerweise in Beziehung zur
Seitenüberschrift; vielmehr ist es nur der Name einer Datei, die im
Unterordner `twocents/` des `content/` Ordner von CMSimple_XH gespeichert wird.
Wenn Sie möchten, können Sie die Kommentare zum selben Thema
auf verschiedenen Seiten anzeigen.

Ein paar Beispiele:

    {{{twocents('gaestebuch')}}}
    {{{twocents('artikel-1')}}}

Um ein bestimmtes Thema als schreibgeschützt zu markieren, so dass nur der
Administrator Kommentare hinzufügen kann, kann `true` als zweites
Funktionsargument übergeben werden, beispielsweise:

    {{{twocents('archiviert', true}}}

Beachten Sie, dass höchstens ein Aufruf von `twocents` auf einer einzelnen
Seite erfolgen darf.

Der äußerst simplistische Spamschutz kann in den Spracheinstellungen unter
`Spam` → `Words`, das eine durch Kommas getrennte Liste von
Wörtern enthält, konfiguriert werden. Kommt eines dieser Wörter in der
Nachricht vor (wobei Groß-/Kleinschreibung keine Rolle spielt), dann wird
der Kommentar automatisch versteckt.

### Administration

Die Administration der Kommentare erfolgt auf den Seiten, auf denen die
Kommentare angezeigt werden. Wenn Sie als Adminstrator angemeldet sind, dann
sehen Sie Schalter zum Bearbeiten, Verstecken/Anzeigen und Löschen der
einzelnen Kommentare.

Im Administrationsbereich des Plugins können Sie, in Abhängigkeit der
Einstellung der Konfigurationsoption `Comments` → `Markup`,
existierende Kommentare von bzw. nach HTML konvertieren. Beachten Sie, dass
es das Beste ist, wenn Sie das nur tun, wenn sich die Website im
Wartungsmodus befindet, und dass Sie nach der Konvertierung diese
Konfigurationsoption umstellen müssen.

Des weiteren gibt es die Möglichkeit Kommentare aus dem
[Comments](https://ge-webdesign.de/cmsimpleplugins/?Eigene_Plugins___Comments)
und dem GBook Plugin zu importieren.
Um diese zu verwenden, müssen Sie die Daten-Dateien des Comments
oder GBook Plugins in den Datenordner von Twocents_XH
(der der Unterordner `twocents/` des `content/` Ordner von CMSimple_XH ist)
kopieren.
Beachten Sie, dass einige Informationen ignoriert werden, z.B. die IP-Adresse
und das hoch geladene Bild, sowie die Formatierung,
wenn Twocents_XH auf einfachen Text konfiguriert ist.

## Einschränkungen

Zur Zeit ist das Posten von Kommentaren von sehr alten Browsern,
wie IE 6 und IE 7, ist nicht möglich;
das Absenden des Formulars schlägt ohne Meldung fehl.

## Problembehebung

Melden Sie Programmfehler und stellen Sie Supportanfragen entweder auf
[Github](https://github.com/cmb69/twocents_xh/issues)
oder im [CMSimple_XH Forum](https://cmsimpleforum.com/).

## Lizenz

Twocents_XH ist freie Software. Sie können es unter den Bedingungen
der GNU General Public License, wie von der Free Software Foundation
veröffentlicht, weitergeben und/oder modifizieren, entweder gemäß
Version 3 der Lizenz oder (nach Ihrer Option) jeder späteren Version.

Die Veröffentlichung von Twocents_XH erfolgt in der Hoffnung, dass es
Ihnen von Nutzen sein wird, aber *ohne irgendeine Garantie*, sogar ohne
die implizite Garantie der *Marktreife* oder der *Verwendbarkeit für einen
bestimmten Zweck*. Details finden Sie in der GNU General Public License.

Sie sollten ein Exemplar der GNU General Public License zusammen mit
Twocents_XH erhalten haben. Falls nicht, siehe <https://www.gnu.org/licenses/>.

Copyright © 2014-2023 Christoph M. Becker

## Danksagung

Twocents_XH verwendet [HTML Purifier](http://htmlpurifier.org/).
Vielen Dank für die Veröffentlichung dieses großartigen Tools unter LGPL.

Das Pluginlogo wurde von [Alessandro Rei](http://www.mentalrey.it/) gestaltet.
Vielen Dank für die Veröffentlichung des Icons unter GPL.

Vielen Dank an die Gemeinschaft im [CMSimple_XH-Forum](http://www.cmsimpleforum.com/)
für Tipps, Anregungen und das Testen.
Besonderer Dank gebührt *frase* und *lck* fürs Testen und wertvolles Feedback.

Und zu guter letzt vielen Dank an [Peter Harteg](http://www.harteg.dk/),
den „Vater“ von CMSimple, und allen Entwicklern von
[CMSimple_XH](https://www.cmsimple-xh.org/de/) ohne die es dieses
phantastische CMS nicht gäbe.
