## Übersicht

Das CaLief‑Modul ergänzt VuFind um einen schlanken Campus‑Lieferdienst für Aufsätze und Buchkapitel. Es bietet:
- eine Bestellmöglichkeit direkt aus der Titeldetailansicht (per Link auf die CaLief‑Bestellseite)
- eine Nutzerregistrierung/Freischaltung für den Dienst
- optionale Bestell‑E‑Mails bzw. Export von Bestelldaten in eine Datei
- Protokollierung in Logdateien und eine kleine Admin‑Übersicht

Wichtig: Der hier vorliegende Code bildet keinen generischen „Delivery“-Baukasten und enthält keine Status‑/Tracking‑Oberflächen und keine Template‑/Theme‑Assets.

## Funktionalität

- Bestelllink für berechtigte Nutzer/innen auf Datensätzen mit passenden Signaturen
- Formularseite mit feldabhängiger Darstellung je nach Format (z. B. Aufsatz vs. Buch)
- Ablage einer Bestellnachricht als E‑Mail (optional) oder in einer Datei (verarbeitbar durch Drittsystem)
- Admin‑Seite zum Freischalten/Sperren von CaLief‑Nutzenden
- Protokollierung von Ereignissen in Logdateien

## Installation

### 1) Modul herunterladen
```bash
cd /path/to/vufind/modules
git clone https://github.com/qcovery/CaLief.git
```

### 2) Modul aktivieren
```apacheconf
SetEnv VUFIND_LOCAL_MODULES CaLief
```

### 3) Datenbanktabellen bereitstellen
Das Modul verwendet die Tabellen `usercalief`, `usercalieflog` und `caliefadmin` über VuFinds DB‑Layer (`RowGateway`/`TableGateway`). Stellen Sie sicher, dass diese Tabellen existieren.

### 4) Konfiguration ablegen
- Erstellen Sie die Datei `local/config/vufind/CaLief.ini` (oder `${VUFIND_LOCAL_DIR}/config/vufind/CaLief.ini`).
- Beispiel siehe unten im Abschnitt „Konfiguration“.

Hinweis: Es gibt in diesem Repository keine `theme/`‑Assets, nichts muss in ein Theme kopiert oder verlinkt werden.

## Routing und Endpunkte

Das Modul registriert den Controller `CaLief\Controller\CaLiefController` mit statischen Routen. Die relevanten Actions sind:
- `/CaLief/Index` – Start/Übersicht für den Dienst (Login erforderlich)
- `/CaLief/Register` – Registrierung für den Dienst (Login erforderlich)
- `/CaLief/Edit` – Bearbeitung eigener Daten (Login erforderlich)
- `/CaLief/Admin` – Admin‑Übersicht (nur für berechtigte Admin‑Benutzer)
- `/CaLief/Order?id=<PPN>` – Bestellformular zu einem Datensatz (Login erforderlich)

Die Links werden im Code teils fest verankert auf `/vufind/CaLief/...` generiert. Passen Sie dies ggf. an Ihre Basis‑URL an.

## Konfiguration (CaLief.ini)

Die Datei wird per `parse_ini_file(..., true)` mit Sektionen geladen. Es gibt:
- eine globale Sektion `[global]`
- je eine Sektion pro Bibliothek/Standort (freier Sektionsname, z. B. `[LUX]`)

Beispiel:
```ini
[global]
; Wenn „1“, werden VuFind‑Nutzer/innen ohne separate CaLief‑Registrierung verwendet.
useCaliefForVufindUsers = 0
; Falls oben „1“, welche Bibliotheks‑Sektion soll verwendet werden?
useCaliefForVufindUsersLibrary = "LUX"
; Admin‑E‑Mail, Absender/Reply‑To für Mails
admin_email = "calief-admin@example.org"
; Link, der im Menü als „Campuslieferdienst“ angezeigt wird
info_link = "/vufind/CaLief/Index"
; Verzeichnis/Datei für Logausgaben
log_dir = "/var/log/vufind"
log_file = "calief.log"
; Verzeichnis für Datei‑Bestellungen (wenn E‑Mail‑Versand nicht genutzt wird)
file_order_dir = "/var/lib/vufind/calief-orders"

[LUX]
; ILN der Bibliothek (wird für die Prüfung der Zugehörigkeit verwendet)
iln = 20
; erwartete Länge der Ausweisnummer (wird bei Registrierung geprüft)
lengthCardNumber = 10
; erlaubte Formate (müssen zu `RecordDriver::getFormats()` passen)
formats[] = Article
formats[] = "electronic Article"
; Sigel-/Lizenz‑Prüfungen für die Verfügbarkeitslogik (regex, Ende‑Match)
sigel_all[] = ".*"               ; Fallback, wenn keine format‑spezifische Liste gesetzt ist
licencenote_all[] = ".*"
licence_all[] = ".*"
; zusätzliche Felder, die im Bestellprozess in die Nachricht übernommen werden
; Format: Beschriftung|… (der Code bildet aus der Beschriftung den Feldnamen, z. B. "Universität" -> "Universität")
additionalFields[] = "Universität|text"
; Pflichtfelder, die beim Absenden geprüft werden (Namen entsprechen den Feldnamen im Formular)
mandantoryFields[] = "email"
; optionale Dateien für die DOD‑Aufbereitung
config_file = "config.xml"
ordermail_file = "ordermail.xml"
; optionale E‑Mail‑Texte (werden im Code als Array `text[...]` angesprochen)
text[emailAuthorizeSubject] = "CaLief: Freigeschaltet"
text[emailAuthorizeBody] = "<html><body>Ihre CaLief‑Nutzung wurde freigeschaltet.</body></html>"
```

Erläuterungen zu wichtigen Schlüsseln:
- `[global].useCaliefForVufindUsers`: Wenn aktiv, werden Basisdaten (Name, E‑Mail, Bibliothekskonto) aus dem VuFind‑Konto übernommen; sonst erfolgt eine eigene Registrierung.
- `[Sektion].formats[]`: steuert, bei welchen Formaten der Bestelllink erscheint.
- `sigel_*`, `licencenote_*`, `licence_*`: werden von `CaLief\Order\Available` verwendet, um anhand von Standort‑ und Lizenzhinweisen eine grundsätzliche Bestellbarkeit zu prüfen.
- `file_order_dir`: Wenn gesetzt, schreibt `orderAction()` eine Datendatei (`lux_import_<timestamp>.asc`), die von einem nachgelagerten Workflow importiert werden kann.

## Integration in Templates

Da das Modul keine eigenen Templates mitliefert, fügen Sie den Bestelllink in Ihren Theme‑Templates selbst ein, z. B. in der Detailansicht (`Record`):
```php
<?php if ($this->auth()->isLoggedIn()): ?>
  <a class="btn btn-primary"
     href="/vufind/CaLief/Order?id=<?=urlencode($this->driver->getUniqueId())?>">
    <?=$this->escapeHtml($this->transEsc('Campuslieferdienst'))?>
  </a>
<?php endif; ?>
```

Hinweise:
- Die im Repository vorhandene Helper‑Klasse `CaLief\CaLief\CaLiefHelper` kann Buttons/Links generieren, ist jedoch in `Module.php` standardmäßig nicht als View‑Helper registriert (der entsprechende Abschnitt ist auskommentiert). Falls Sie sie nutzen möchten, registrieren Sie den Helper in `getViewHelperConfig()` analog zur auskommentierten Vorlage.

## Verhalten und Ablauf (Kurzüberblick)

1) Nutzer/innen rufen `/CaLief/Order?id=<PPN>` auf (der Link wird von Ihnen im Theme platziert).
2) Die Action prüft Berechtigung/Autorisierung, ermittelt Format und Felder und zeigt das Formular.
3) Beim Absenden werden Pflichtfelder geprüft. Bei Erfolg wird entweder
   - eine E‑Mail erzeugt (siehe `caliefMail('order', ...)` – aktuell auskommentierte Beispiele) oder
   - eine Bestelldatei in `file_order_dir` abgelegt (`lux_import_<timestamp>.asc`).
4) Optional wird der Status des Nutzers erneuert (`caliefAuthorize(..., 'renew')`).

## Troubleshooting

- Kein Button sichtbar
  - Link im Template korrekt gesetzt? (`/CaLief/Order?id=...`)
  - Nutzer eingeloggt? Nur eingeloggte Nutzer/innen können bestellen.
  - `CaLief.ini` vorhanden und korrekt? Insbesondere `[global]` und die Bibliotheks‑Sektion.
- „Nicht berechtigt“/Weiterleitung auf Index
  - Nutzer in `usercalief` freigeschaltet (`authorized = 1`)? Oder `useCaliefForVufindUsers = 1` gesetzt?
- „Artikel nicht verfügbar“
  - Prüfen Sie ILN/Sigel/Lizenz‑Konfiguration (`iln`, `sigel_*`, `licencenote_*`, `licence_*`).
- E‑Mails kommen nicht an
  - Der E‑Mail‑Versand ist im Code teils auskommentiert; standardmäßig wird die Dateiablage genutzt. Aktivieren/prüfen Sie ggf. den Mailversand in `caliefMail()`.
- Logs fehlen
  - `log_dir` muss existieren und für den Webserver schreibbar sein.
- Dateiablage funktioniert nicht
  - `file_order_dir` muss existieren und beschreibbar sein.

## Technische Details

### Abhängigkeiten:
- VuFind ≥ 9.0
- PHP ≥ 8.2 (je nach Ihrer VuFind‑Version)

### Relevante Klassen:
  - `CaLief\Controller\CaLiefController` (Actions, Bestelllogik)
  - `CaLief\Order\Available` (Prüfung Sigel/Lizenzen/Formate)
  - `CaLief\CaLief\CaLiefHelper` (optional: Link/Buttons)
  - `CaLief\Db\*` (Tabellen/Zeilen‑Wrapper)

### Verzeichnisstruktur (vereinfacht)
```
CaLief/
├── Module.php
├── config/
│   └── module.config.php
└── src/
    └── CaLief/
        ├── Controller/
        ├── Order/
        ├── Db/
        ├── Model/
        └── CaLief/
```

## Code und Weiterentwicklung

- In `Module.php` kann der View‑Helper registriert werden (Abschnitt ist vorbereitet, aber auskommentiert).
- Der E‑Mail‑Versand in `caliefMail()` ist teilweise auskommentiert und kann projektspezifisch aktiviert/angepasst werden.
- Die Logik in `Available.php` nutzt aktuell Platzhalter/Kommentare für Solr‑Felder (`getSignatureData`/`getLicenceData` sind im Code nicht aktiv). Passen Sie dies ggf. an Ihre lokalen Solr‑Felder an.

**GitHub Repository:** [github.com/qcovery/CaLief](https://github.com/qcovery/CaLief)

---

*Diese Dokumentation richtet sich an VuFind‑Administratoren und technisch versierte Bibliotheksmitarbeitende. Für Entwicklerdetails siehe die Inline‑Kommentare im Code.*
