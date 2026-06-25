# CleanUpUserData
Dieses Modul stellt einen Konsolenbefehl bereit, der bei veraltete Benutzerdaten die persönlichen Angaben der Nutzenden in der Datenbank bereinigt.

## Beschreibung
Das CleanUpUserData-Modul erweitert VuFind um einen Wartungsbefehl zur Datenbankbereinigung. Die Hauptfunktionen umfassen:

- Bereinigen von Benutzerdatensätzen, deren letzter Login älter als eine definierte Stundenanzahl ist
- Konfigurierbare Zeitgrenze über den Parameter `--hours` (Standardwert: 24 Stunden)
- Bereitstellung eines Bash-Skripts für den einfachen Einsatz als Cronjob

## Installation
### Voraussetzungen
- VuFind 5.0 oder höher
- PHP 7.0 oder höher

### Installation
1. Integrieren Sie das Modul im `module`-Verzeichnis von VuFind.
2. Aktivieren Sie das Modul, indem Sie `CleanUpUserData` zur Liste der aktiven Module hinzufügen (z. B. in der Umgebungsvariable `VUFIND_LOCAL_MODULES`).

## Verwendung
### Konsolenbefehl
Der Befehl kann direkt über die VuFind-Konsole aufgerufen werden:

```bash
php public/index.php util/cleanup_user_data
```

Mit optionalem `--hours`-Parameter:

```bash
php public/index.php util/cleanup_user_data --hours 48
```

### Parameter
| Parameter | Beschreibung                                                                             | Standardwert |
|-----------|------------------------------------------------------------------------------------------|--------------|
| `--hours` | Zeitraum in Stunden – Benutzer, deren letzter Login länger zurückliegt, werden bereinigt | 24 |

### Bash-Skript
Im Verzeichnis `scripts/` befindet sich das Skript `clean_up_user_data.sh`, das die notwendigen Umgebungsvariablen setzt und den Befehl ausführt:

```bash
# Ohne Parameter (Standard: 24 Stunden)
./scripts/clean_up_user_data.sh

# Mit benutzerdefinierter Stundenanzahl
./scripts/clean_up_user_data.sh 48
```

Das Skript setzt folgende Umgebungsvariablen:
- `VUFIND_HOME`: Pfad zur VuFind-Installation (Standard: `/var/www/html`)
- `VUFIND_LOCAL_DIR`: Pfad zur lokalen Konfiguration
- `VUFIND_LOCAL_MODULES`: Aktivierte lokale Module

Das Skript eignet sich für den Einsatz als Cronjob, um die Datenbankbereinigung regelmäßig und automatisiert durchzuführen.

## Technische Details
### Modulstruktur
```
CleanUpUserData/
├── config/
│   └── module.config.php          # Modulkonfiguration
├── scripts/
│   └── clean_up_user_data.sh      # Bash-Skript für den Aufruf
├── src/CleanUpUserData/
│   └── Command/Util/
│       ├── CleanUpUserDataCommand.php        # Konsolenbefehl
│       └── CleanUpUserDataCommandFactory.php # Factory für den Befehl
└── Module.php                     # Modulklasse
```

### Befehlsname
- `util/cleanup_user_data`

## Lizenz
Dieses Modul ist unter der GNU General Public License v2.0 lizenziert.
