## Übersicht

Das ListAdmin‑Modul ergänzt VuFind um eine kleine Administrationsfunktion für Merklisten. Es erlaubt berechtigten Administrator‑Accounts, alle Listen (inkl. zugehöriger Einträge) von einem Nutzerkonto auf ein anderes zu übertragen – z. B. bei Umbenennungen, Konsolidierung von Benutzerkonten oder beim Wechsel der Authentifizierungsquelle.

## Funktionalität

- Admin‑geschützte Oberfläche zum Migrieren von Benutzer‑Listen
- Formular mit Plausibilitätsprüfung (Quell‑ und Ziel‑Account müssen existieren)
- Übernahme der Listen und Listeneinträge auf Datenbankebene
- Nutzung bestehender VuFind‑Tabellen (`user`, `userlist`, `user_resource`) – keine neuen Tabellen nötig
- Einbindung in den MyResearch‑Bereich (linksbündiges Menü)

## Installation

### 1) Modul herunterladen
```bash
cd /path/to/vufind/modules
git clone https://github.com/qcovery/ListAdmin
```

### 2) Modul aktivieren
```apacheconf
SetEnv VUFIND_LOCAL_MODULES ListAdmin
```

### 3) Konfiguration bereitstellen
Kopieren Sie die Beispiel‑Konfiguration `config/vufind/ListAdmin.ini` in Ihr lokales Konfigurationsverzeichnis und passen Sie sie an.

### 4) Theme‑Assets einbinden
Das Modul bringt ein View‑Template für die Migrationsseite mit. Es kann per Theme‑Mixin eingebunden werden.
Kopieren oder verlinken Sie das `theme`-Verzeichnis im `themes`-Verzeichnis Ihrer VuFind-Installation und ergänzen Sie das Mixin in Ihrem bestehenden Theme:
```php
'mixins' => [
    'listadmin'
]
```

## Konfiguration

Zentrale Datei: `ListAdmin.ini`

```ini
[ListAdmin]
; Liste der Benutzerkennungen, die als Admins zugelassen sind
admins[] = 123
admins[] = 456
admins[] = 789
```
`admins[]` definiert die Liste der Nutzerkennungen, die das Migrations‑Interface nutzen dürfen.

## Verwendung und Template‑Integration

- Navigieren Sie als berechtigter Admin zu: `/ListAdmin/migrate`. Nicht‑Admins werden auf die Startseite des Benutzerbereichs umgeleitet.
- Formularfelder:
  - "Old account": Quell‑Benutzername
  - "New account": Ziel‑Benutzername
- Nach Absenden prüft das Modul, ob beide Konten existieren, und verschiebt dann alle Listen (inkl. Einträge) vom alten auf das neue Konto. Es gibt eine Rückmeldung über Erfolg/Fehler.

Integration im Template:
- View‑Template der Migrationsseite: `module/ListAdmin/theme/templates/listadmin/migrate.phtml`
- Ein Link auf die Migrationsseite kann mit folgendem Code eingebunden werden:

```php
<? if ($this->ListAdmin()->isAdmin($user)): ?>
    <a href="<?=$this->url('listadmin-migrate') ?>">
        <?=$this->transEsc('ListAdmin migration') ?>
    </a>
<? endif; ?>
```

## Troubleshooting

- Kein Zugriff auf die Seite / Umleitung zur MyResearch‑Startseite:
  - Ist der aktuelle Benutzer in `ListAdmin.ini` unter `admins[]` eingetragen?
  - Ist der Benutzer eingeloggt?
- Fehlermeldung „Old/New user account does not exist“:
  - Stimmen die Nutzerkennungen exakt mit den in der VuFind‑Datenbank vorhandenen `user.username`‑Werten überein?
- Nach Migration fehlen Einträge:
  - Prüfen Sie, ob es sich um persönliche Listen handelte; die Migration betrifft `userlist` und `user_resource` Einträge des Quellkontos.
- Route nicht gefunden `/ListAdmin/migrate`:
  - Ist das Modul aktiviert (`VUFIND_LOCAL_MODULES`)?
  - Wurde der Webserver/Cache nach Installation neu geladen?

## Technische Details

### Abhängigkeiten
- VuFind ≥ 9.0
- PHP ≥ 7.4 (entsprechend Code‑Header); empfohlen ≥ 8.2 je nach Ihrer VuFind‑Version

### Verzeichnisstruktur
```
ListAdmin/
├── config/
│   ├── module.config.php          # Statische Route, Controller‑Registrierung
│   └── vufind/
│       └── ListAdmin.ini          # Beispiel‑Konfiguration
├── src/
│   └── ListAdmin/
│       ├── Controller/
│       │   ├── ListAdminController.php
│       │   └── ListAdminControllerFactory.php
│       └── View/
│           └── Helper/ListAdmin/
│               ├── ListAdmin.php
│               └── ListAdminFactory.php
├── theme/
│   ├── mixin.config.php
│   └── templates/
│       └── listadmin/
│           └── migrate.phtml
└── Module.php
```

### Datenbank
- Es werden ausschließlich bestehende Tabellen verwendet: `user`, `userlist`, `user_resource`.
- Keine zusätzlichen SQL‑Migrationsskripte erforderlich.

## Code und Weiterentwicklung

**GitHub Repository:** [https://github.com/qcovery/ListAdmin](https://github.com/qcovery/ListAdmin)

—

*Diese Dokumentation richtet sich an VuFind‑Administratorinnen/Administratoren und technisch versierte Bibliotheksmitarbeitende; für Entwicklerdetails siehe die Inline‑Kommentare und Klassen im Code.*