# FulltextFinder

Das Modul integriert EBSCO Full Text Finder (FTF) in VuFind. Es ermittelt asynchron (per AJAX) verfügbare Volltext‑Ziel‑Links für den aktuell angezeigten Datensatz und blendet diese als Liste ein. Falls keine direkten Links verfügbar sind, wird ein Fallback‑Link „Verfügbarkeit prüfen“ angezeigt.

## Installation

### 1) Modul herunterladen
```bash
cd /path/to/vufind/modules
git clone https://github.com/qcovery/FulltextFinder
```

### 2) Modul aktivieren
```apacheconf
SetEnv VUFIND_LOCAL_MODULES FulltextFinder
```

### 3) Template einbinden
Kopieren oder verlinken Sie das `theme`-Verzeichnis im `themes`-Verzeichnis Ihrer VuFind Installation und ergänzen Sie das Modul in der `theme.config.php` Ihres Themes als mixin:
```php
'mixins' => [
    'fulltextfinder'
]
```
Sie können das notwendige Template dann in Ihrem Template einbinden:
```php
<!-- Beispiel: in einem Record-Template -->
<?= $this->render('fulltextfinder/ajax.phtml', [
   'driver' => $this->driver,
   'list' => $this->list ?? '',
   'searchClassId' => $this->searchClassId ?? ''
]) ?>
```


### 4) Konfiguration
Hinterlegen Sie eine `FulltextFinder.ini` in Ihrem lokalen Konfigurationsverzeichnis.  
**(Aktuell fehlt eine entsprechende Beispieldatei im Repository.)**

```ini
[FulltextFinder]
; EBSCO FTF Konto-ID (wird in die API‑URL eingesetzt)
account = "<IhreAccountId>"

; EBSCO FTF Passwort (wird als HTTP-Header "password" gesendet)
password = "<IhrPasswort>"

; Optional: Sichtbare Kategorien und maximale Linkanzahl pro Kategorie
; Syntax pro Zeile: Kategorie|MaxAnzahl
; MaxAnzahl weglassen oder -1 = unbegrenzt
; Mehrere Einträge möglich (mehrere Zeilen)
; categories[] = "OpenAccess|-1"
; categories[] = "Publisher|2"
; categories[] = "Database|1"
```

## Technische Details

### Abhängigkeiten
- VuFind ≥ 9.0
- PHP ≥ 8.2 (je nach Ihrer VuFind‑Version)
- EBSCO Full Text Finder Service

### Dateistruktur
```
FulltextFinder/ 
 ├── config/
 │   └── module.config.php               # Modul-Konfiguration
 ├── src/ 
 │   └── FulltextFinder/
 │       ├── AjaxHandler/              
 │       │   └── FulltextFinder.php      # Ajax-Handler
 │       ├── View/                     
 │       │   └── Helper/
 │       │       └── FulltextFinder.php  # View-Helpers
 ├── theme/ 
 │   ├── css/                           
 │   │   └── fulltextfinder.css          # Styling
 │   ├── js/ 
 │   │   └── fulltextfinder.js           # JavaScript-Funktionalität 
 │   ├── templates/                      # Template-Dateien
 │   │   └── fulltextfinder/
 │   │       ├── ajax.phtml
 │   │       └── result.phtml
 │   └── mixin.config.php                # Einbinden von CSS und JS, Registierung des ViewHelpers
 └── Module.php                          # Modul-Bootstrap
```

## Code und Weiterentwicklung

- **GitHub Repository:** [https://github.com/qcovery/FulltextFinder](https://github.com/qcovery/FulltextFinder)
- **EBSCO Full Text Finder**: [https://www.ebsco.com/de-de/produkte/full-text-finder](https://www.ebsco.com/de-de/produkte/full-text-finder)

---

*Diese Dokumentation richtet sich an VuFind-Administratoren und Bibliotheksmitarbeitende mit technischem Verständnis. Für Entwickler-Informationen siehe die Inline-Kommentare im Code.*
