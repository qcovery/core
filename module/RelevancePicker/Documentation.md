## Übersicht

Das **RelevancePicker-Modul** erweitert die Suchergebnisliste um eine Anzeige, welche die Grundlagen für Relevanz-Berechnungen des Index bei jedem einzelnen Treffer visualisiert.

Dafür ergänzt das Modul VuFinds Suchkomponenten, indem es Solr‑„Explain“-Informationen aus Suchergebnissen extrahiert und für die Anzeige/Analyse bereitstellt. Es ersetzt dazu ausgewählte VuFind‑Services (Backend/Params/Results) durch kompatible Ableitungen und erweitert die Ergebnisobjekte um eine Methode zur Abfrage dieser Explain‑Daten.

## Funktionalität

- Extraktion von Solr‑Explain‑Daten aus `debug.explain` (bei aktivem `debugQuery`).
- Bereitstellung der Explain‑Daten pro Treffer über `RelevancePicker\Search\Solr\Results::getExplain()`. Die Daten sind als assoziatives Array nach Dokument‑ID (PPN) indiziert.
- Die RecordCollection verwirft den Solr Debug‑Abschnitt, daher hängt die `RecordCollectionFactory` des Moduls die Explain-Daten an den jeweiligen Treffer,
  bevor die Record‑Driver gebaut werden.
- Ergänzen einer kleinen Grafik mit Tooltip an jedem Treffer in der Ergebnisliste.

## Installation

### 1) Modul herunterladen
```bash
cd /path/to/vufind/modules
git clone https://github.com/qcovery/RelevancePicker
```

### 2) Modul aktivieren
```apacheconf
SetEnv VUFIND_LOCAL_MODULES RelevancePicker
```

**Reihenfolge beachten:** Params und Results werden über die VuFind Plugin‑Manager‑Konfiguration registriert. 
Bei mehreren Modulen entscheidet die Reihenfolge in `modules.conf`.
RelevancePicker muss daher **nach** Modulen stehen, die dieselben Services registrieren – insbesondere nach
`Libraries` und `SearchKeys` – damit z.B. die Methode `getExplain()` verfügbar ist.

### 3) Theme einbinden
Kopieren oder verlinken Sie das `theme`-Verzeichnis des Moduls in das `themes`-Verzeichnis Ihrer VuFind-Installation und ergänzen Sie das Modul in der `theme.config.php` Ihres Themes als mixin:
```php
'mixins' => [
    'relevancepicker'
]
```

### 4) Ausgabe im eigenen Theme ergänzen
Binden Sie das folgende Snippet in dem entsprechenden Template (z.B. `search/list-list.phtml`) Ihres Themes ein, um die RelevancePicker-Anzeige in der Ergebnisliste hinzuzufügen:
```php
<?=$this->render('search/relevancepicker-list-list.phtml', ['id' => $current->getUniqueId(), 'results' => $this->results]) ?>
```

## Troubleshooting

- Keine Explain‑Daten sichtbar:
  - Stellen Sie sicher, dass die RelevancePicker‑Modul aktiv ist und das Snippet in Ihrem Theme eingebunden ist.
  - Prüfen Sie, ob `debugQuery=true` gesetzt ist und Solr `debug.explain` liefert.
  - Verwenden Sie das Solr‑Backend `solr`; andere Backends – auch `search2` –
    liefern keine Explain‑Daten, da nur `solr` registriert wird.
  - Prüfen Sie die Reihenfolge in `modules.conf` (siehe Installation, Schritt 2).


## Technische Details

### Abhängigkeiten
- VuFind ≥ 10.0
- PHP ≥ 8.2 (je nach Ihrer VuFind‑Version)
- Apache Solr mit aktivierbarem `debugQuery`

### Wichtige Klassen (Auszug)
- `RelevancePicker\Backend\Solr\Response\Json\RecordCollectionFactory`
  - Erweitert die VuFind‑RecordCollectionFactory und hängt die Explain-Daten aus `debug.explain` an den jeweiligen Treffer.
- `RelevancePicker\Search\Solr\Results`
  - Erweitert `Libraries\Search\Solr\Results` und stellt `getExplain()` bereit, indem es die Explain-Daten aus den Treffern liest.
- `RelevancePicker\Search\Results\ResultsFactory`, `RelevancePicker\Search\Solr\ResultsFactory`
  - Erstellen Results‑Instanzen; konfigurieren u. a. den Spelling‑Processor.
- `RelevancePicker\Search\Params\ParamsFactory`, `RelevancePicker\Search\Solr\ParamsFactory`
  - Erzeugen Params‑Instanzen (Options/Helpers werden injiziert).
- `RelevancePicker\Search\Factory\SolrDefaultBackendFactory`
  - Setzt die RecordCollectionFactory des Solr‑Backends für Explain‑Support.
- `RelevancePicker\Search\BackendManager`
  - Alias/Wrapper für VuFinds BackendManager innerhalb dieses Moduls.

### Dateistruktur (vereinfacht)
```
RelevancePicker/
├── Module.php
├── config/
│   └── module.config.php
└── src/
    └── RelevancePicker/
        ├── Backend/
        │   └── Solr/Response/Json/RecordCollectionFactory.php
        ├── Search/
        │   ├── Factory/SolrDefaultBackendFactory.php
        │   └── Solr/
        │       ├── Params.php
        │       └── Results.php
        └── templates/             # Snippet für Ergebnisliste
```

## Code und Weiterentwicklung

**GitHub Repository:** [https://github.com/qcovery/RelevancePicker](https://github.com/qcovery/RelevancePicker)

---

*Diese Dokumentation richtet sich an VuFind‑Administratorinnen/Administratoren und technisch versierte Bibliotheksmitarbeitende; für Entwicklerdetails siehe die Inline‑Kommentare und Klassen im Code.*
