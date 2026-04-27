## Übersicht

Das BeluginoCover‑Modul ersetzt/erweitert die Standard‑Cover‑Auslieferung von VuFind. Es nutzt weiterhin alle regulären VuFind‑Coverquellen (z. B. OpenLibrary, Google, lokale Dateien). Falls darüber kein Bild gefunden wird, erzeugt das Modul ein dynamisches Fallback‑Cover als PNG. Dieser Fallback basiert auf den Belugino‑Piktogrammen und kann je nach Medienformat (Parameter `format`) ein passendes Icon mit Schrift ausgeben.

## Funktionalität

- Nutzt die bestehenden VuFind‑Coverquellen (geerbt von `\VuFind\Cover\Loader`).
- Akzeptiert zusätzlich `format` und generiert dann, falls kein echtes Cover gefunden wird, ein Fallback‑Bild mithilfe der "belugino"-Icons.
- Akzeptiert die üblichen Cover‑Parameter (ISBN/ISSN/OCLC/UPC, etc.).
- Greift auf VuFinds Caching für Cover zu (Cache „cover“).
- Ersetzt den Standard‑Controller/Loader von VuFind über Service‑Aliase – daher keine Template‑Anpassungen nötig.

## Installation

### 1) Modul herunterladen
```bash
cd /path/to/vufind/modules
git clone https://github.com/qcovery/BeluginoCover.git
```

### 2) Modul aktivieren
- In `config/vufind/config.ini` (oder per Umgebungsvariable) sicherstellen, dass `BeluginoCover` in `VUFIND_LOCAL_MODULES[]` enthalten ist:
```ini
VUFIND_LOCAL_MODULES[] = BeluginoCover
```

### 3) Belugino‑Assets bereitstellen (erforderlich für die Darstellung der Icons)
- Der Fallback rendert Text/Glyphen anhand von Dateien im Theme `base` bzw. `belugax` (hart verdrahtete Pfade im Code):
  - `APPLICATION_PATH/themes/base/css/belugino.css`
  - `APPLICATION_PATH/themes/base/css/fonts/belugino.ttf`
- Stellen Sie sicher, dass diese Dateien existieren. Falls Ihr Theme anders heißt, müssen die Dateien in genau diesen Pfaden vorhanden sein (oder der Code entsprechend angepasst werden).

### 4) BelugaConfig.ini anlegen/prüfen
- Der Loader lädt Mapping‑Informationen aus:
  - `${VUFIND_LOCAL_DIR}/config/vufind/BelugaConfig.ini`
- Beispiel (`[belugino]`‑Sektion):
```ini
[belugino]
Book = book
Article = article
Journal = journal
DVD = dvd
```
Die Werte (z. B. `book`, `journal`) verweisen auf CSS‑Klassennamen, die in `belugino.css` definiert sind. Das Modul sucht in der CSS nach `.klassename:before { content: "…" }` und rendert dieses „content“ als Glyph/Text.

### 5) PHP‑Erweiterungen
- Benötigt GD mit FreeType‑Unterstützung (für `imagettftext`).


## Konfiguration

Die Cover‑Quellen konfigurieren Sie wie gewohnt in VuFinds `config.ini` unter `[Content]` (dies ist Kern‑VuFind, nicht Teil des Moduls). Relevante Beispiele:
```ini
[Content]
; Steuert dynamische Standard‑Covers von VuFind selbst (optional, zusätzlich zum Belugino‑Fallback)
makeDynamicCovers = true

; Optionales Fail‑Bild, wenn gar nichts generiert werden kann
noCoverAvailableImage = "/themes/root/images/noCover2.gif"

; Aktivierung bekannter Quellen (abhängig von Ihrer VuFind‑Version/Installation)
; example settings – bitte VuFind‑Doku konsultieren
openLibraryCovers = true
googleCovers = true
```

Belugino‑spezifisch gibt es keine eigene `*.ini` außer der oben genannten `BelugaConfig.ini` mit der `[belugino]`‑Sektion (Mapping Format → CSS‑Klasse).

## Verwendung

Das Modul hängt sich an die bestehenden VuFind‑Cover‑Endpunkte (Controller‑Alias `Cover`). Sie müssen in Templates nichts Spezielles von Belugino einbinden.

Typische Aufrufe (Beispiele):
- Per ISBN (klein):
  - `/Cover/Show?isbn=9781234567897&size=small`
- Per ISBN (mittel):
  - `/Cover/Show?isbn=9781234567897&size=medium`
- Formatgesteuertes Fallback (wenn kein echtes Cover gefunden wird):
  - `/Cover/Show?format=Book&size=medium`

Akzeptierte Query‑Parameter (Auszug – identisch zu VuFind, plus `format`):
- `isbn`, `issn`, `oclc`, `upc`, `recordid`, `source`, `title`, `author`, `callnumber`, `type`, `size` (`small`|`medium`), sowie `format` (frei, wird mit `[belugino]` gemappt).

Hinweise zur Größe:
- Das Modul unterscheidet intern primär zwischen `small` (Standard) und `medium`. Für `medium` werden Rendering‑Parameter (Größe/Offsets) hochskaliert.

## Beispiele

- Fallback‑Icon für „Journal“ (Glyph aus `belugino.css`):
  ```
  /Cover/Show?format=Journal&size=medium
  ```
- Standard: ISBN‑Cover, falls vorhanden, sonst Fallback gemäß `format` (wenn `format` zusätzlich übergeben wird):
  ```
  /Cover/Show?isbn=9783161484100&format=Book
  ```

## Troubleshooting

- **Kein Bild/404 beim Fallback**
  - Prüfen, ob `APPLICATION_PATH/themes/base/css/belugino.css` und die Belugino-Schrift-Dateien `.../fonts/belugino.[eot|svg|ttf|woff]` vorhanden und lesbar sind.
  - Prüfen, ob `${VUFIND_LOCAL_DIR}` korrekt gesetzt ist und `config/vufind/BelugaConfig.ini` existiert.
- **Fallback zeigt falsches Symbol/Zeichen**
  - Stimmt der `format`‑Parameter mit einem Schlüssel in `[belugino]` überein? (Vergleiche werden intern in Kleinbuchstaben/ohne Leerzeichen gemacht.)
  - Entspricht der Wert in `[belugino]` einer Klasse in `belugino.css` mit `:before { content: "…" }`?
- **Schrift wird nicht gezeichnet**
  - GD mit FreeType muss installiert/aktiviert sein.
  - Dateipfade und Berechtigungen für `belugino.[eot|svg|ttf|woff]` prüfen.
- **Externe Coverquellen liefern nichts**
  - Einstellungen unter `[Content]` in VuFinds `config.ini` prüfen (API‑Freischaltungen, Netzwerkzugang, etc.).
- **Cache/Leistung**
  - Der Loader nutzt VuFinds „cover“‑Cache‑Verzeichnis. Schreibrechte prüfen, ggf. Cache leeren.

## Technische Details

### Abhängigkeiten
- VuFind ≥ 7.0
- PHP ≥ 7.4
- Laminas Framework
- GD + FreeType
- Sabberworm CSS Parser (über VuFind‑Abhängigkeiten verfügbar)
- CSS- und Font-Dateien für die "belugino"-Icons aus den Themes `base` oder `belugax`

### Dateistruktur
```
BeluginoCover/
├── config/
│   └── module.config.php
├── src/
│   └── BeluginoCover/
│       ├── Controller/
│       │   ├── CoverController.php
│       │   └── CoverControllerFactory.php
│       └── Cover/
│           ├── Loader.php
│           └── LoaderFactory.php
└── Module.php
```

### Routing/Services
- In `config/module.config.php` wird der VuFind‑`Cover`‑Controller auf `BeluginoCover\Controller\CoverController` umgebogen (Aliase `cover`/`Cover`).
- Der `\VuFind\Cover\Loader` wird durch `BeluginoCover\Cover\Loader` ersetzt (Service `BeluginoCover\Cover\Loader`).
- Es werden keine neuen Routen eingeführt; bestehende Endpunkte funktionieren weiter.

## Code und Weiterentwicklung
**GitHub Repository:** [github.com/qcovery/BeluginoCover](https://github.com/qcovery/BeluginoCover)


---

*Diese Dokumentation richtet sich an VuFind‑Administratoren und technisch versierte Bibliotheksmitarbeitende. Für Entwicklerdetails siehe die Inline‑Kommentare im Code.*
