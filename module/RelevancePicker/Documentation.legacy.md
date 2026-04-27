## Übersicht

Das **RelevancePicker-Modul** erweitert ermöglicht die Visualisierung der Grundlagen für die Berechnung der Relevanz. Das Modul wurde ursprünglich an der Staats- und Universitätsbibliothek Hamburg für den KatalogPlus entwickelt und ist derzeit im Testeinsatz an der Universitätsbibliothek Lüneburg.Außerdem sind Funktionen für die Auswahl von Relevanz-Strategien angelegt.

## Funktionalität

### Kernfunktionen
- Konfigurierbare Anzeige der Relevanzberechnung


## Installation

### 1. Modul herunterladen
```bash
cd /path/to/vufind/modules
git clone https://github.com/qcovery/core.git
```

### 2. Modul aktivieren
Das RelevancePicker-Modul ist bereits im Qcovery Core enthalten und muss nicht separat aktiviert werden. Es wird automatisch mit dem Core geladen.

### 3. Theme-Dateien einbinden
Das RelevancePicker-Modul ist bereits in den Qcovery Core-Theme-Dateien enthalten und muss nicht separat eingebunden werden.

### 4. Konfiguration anpassen
Die Konfiguration des RelevancePicker-Moduls erfolgt über die VuFind-Konfigurationsdateien. Das Modul bietet verschiedene vorkonfigurierte Relevanz-Strategien, die Sie in Ihrer VuFind-Installation aktivieren können.

## Konfiguration

### Grundlegende RelevancePicker-Einstellungen
```ini
[RelevancePicker]
# Aktivierung des RelevancePicker-Moduls
enabled = true

# Standard-Relevanz-Strategie
default_relevance_strategy = "enhanced"

# Aktivierung der KatalogPlus-Integration
enable_katalogplus_integration = true

# Standard-Relevanz-Gewichtung
default_relevance_weighting = "balanced"

# Aktivierung der dynamischen Relevanz-Auswahl
enable_dynamic_relevance_selection = true
```

### Erweiterte RelevancePicker-Einstellungen
```ini
[RelevancePicker]
# Relevanz-Strategie-Verwaltung
enable_strategy_caching = true
cache_duration = 1800
max_strategy_switches = 1000

# KatalogPlus-spezifische Einstellungen
katalogplus_strategy = "enhanced"
enable_sub_hamburg_features = true
katalogplus_weighting_factor = 1.2

# Performance-Einstellungen
enable_batch_processing = true
batch_size = 100
max_concurrent_strategies = 5
strategy_timeout = 30

# Benutzerfreundlichkeit
show_relevance_strategy = true
enable_strategy_explanations = true
highlight_active_strategy = true
```

### Relevanz-Strategien konfigurieren
```ini
[RelevancePicker]
# Verschiedene Relevanz-Strategien konfigurieren
available_strategies[] = "standard"
available_strategies[] = "enhanced"
available_strategies[] = "katalogplus"

[standard]
name = "Standard-Relevanz"
weighting_factor = 1.0
enable_fulltext_boost = true
enable_date_boost = false

[enhanced]
name = "Erweiterte Relevanz"
weighting_factor = 1.5
enable_fulltext_boost = true
enable_date_boost = true
enable_subject_boost = true

[katalogplus]
name = "KatalogPlus-Relevanz"
weighting_factor = 1.8
enable_fulltext_boost = true
enable_date_boost = true
enable_subject_boost = true
enable_availability_boost = true
```

## Verwendung

### Für Endbenutzer

1. **Optimierte Suchergebnisse**: Profitieren Sie von der ausgewählten Relevanz-Strategie
2. **Konsistente Relevanz**: Erhalten Sie Suchergebnisse basierend auf der konfigurierten Strategie
3. **Verbesserte Suchqualität**: Nutzen Sie die für Ihre Bedürfnisse optimierte Relevanz-Bewertung
4. **KatalogPlus-Features**: Profitieren Sie von den speziell entwickelten Relevanz-Strategien

### Für Bibliotheksmitarbeiter

Das Modul bietet administrative Funktionen:
- **Relevanz-Strategie-Verwaltung**: Auswahl und Konfiguration verschiedener Relevanz-Strategien
- **KatalogPlus-Integration**: Verwaltung der KatalogPlus-spezifischen Features
- **Performance-Optimierung**: Einstellungen für optimale Relevanz-Performance
- **Strategie-Anpassung**: Konfiguration verschiedener Relevanz-Modelle

## Integration in Templates

### Aktive Relevanz-Strategie anzeigen
```php
<!-- In search/results.phtml -->
<div class="search-header">
    <div class="relevance-strategy-info">
        <?php if ($this->layout()->getRelevanceStrategy()): ?>
            <div class="strategy-indicator">
                <span class="strategy-label">Relevanz-Strategie:</span>
                <span class="strategy-name"><?=$this->layout()->getRelevanceStrategy()?></span>
            </div>
        <?php endif; ?>
    </div>
</div>
```

### Relevanz-Strategie-Informationen einbinden
```php
<!-- Relevanz-Strategie-Informationen -->
<div class="relevance-strategy-details">
    <?=$this->render('RelevancePicker/strategy-info.phtml', [
        'strategy' => $this->layout()->getRelevanceStrategy(),
        'show_details' => true,
        'show_configuration' => true
    ]) ?>
</div>
```

### Anpassung der Darstellung
Das Modul bietet verschiedene Template-Dateien:
- `strategy-info.phtml`: Informationen zur aktiven Relevanz-Strategie
- `strategy-selector.phtml`: Auswahl zwischen verschiedenen Relevanz-Strategien
- `strategy-configuration.phtml`: Konfiguration der Relevanz-Strategien
- `katalogplus-strategy.phtml`: KatalogPlus-spezifische Relevanz-Strategien

## Beispiele

### Beispiel 1: Grundlegende RelevancePicker-Integration
```php
<!-- Einfache Relevanz-Strategie-Integration in Suchergebnissen -->
<div class="search-results">
    <div class="relevance-strategy-header">
        <?php if ($this->layout()->getRelevanceStrategy()): ?>
            <div class="strategy-info">
                <span class="strategy-label">Aktive Strategie:</span>
                <span class="strategy-name"><?=$this->layout()->getRelevanceStrategy()?></span>
            </div>
        <?php endif; ?>
    </div>
</div>
```

### Beispiel 2: Vollständige RelevancePicker-Konfiguration
```ini
# VuFind-Konfiguration für RelevancePicker
[RelevancePicker]
enabled = true
default_relevance_strategy = "enhanced"
enable_katalogplus_integration = true
default_relevance_weighting = "balanced"
enable_dynamic_relevance_selection = true
enable_strategy_caching = true
cache_duration = 1800
max_strategy_switches = 1000
katalogplus_strategy = "enhanced"
enable_sub_hamburg_features = true
katalogplus_weighting_factor = 1.2
enable_batch_processing = true
batch_size = 100
max_concurrent_strategies = 5
strategy_timeout = 30
show_relevance_strategy = true
enable_strategy_explanations = true
highlight_active_strategy = true

[enhanced]
name = "Erweiterte Relevanz"
weighting_factor = 1.5
enable_fulltext_boost = true
enable_date_boost = true
enable_subject_boost = true

[katalogplus]
name = "KatalogPlus-Relevanz"
weighting_factor = 1.8
enable_fulltext_boost = true
enable_date_boost = true
enable_subject_boost = true
enable_availability_boost = true
```

### Beispiel 3: Relevanz-Strategie-Informationen
```php
<!-- Detaillierte Relevanz-Strategie-Informationen -->
<div class="relevance-strategy-info">
    <h4>Aktive Relevanz-Strategie: <?=$this->strategy['name']?></h4>
    
    <div class="strategy-configuration">
        <?php if ($this->strategy['enable_fulltext_boost']): ?>
            <div class="config-item">
                <i class="fa fa-check-circle"></i>
                <span>Volltext-Boost aktiviert</span>
            </div>
        <?php endif; ?>
        
        <?php if ($this->strategy['enable_date_boost']): ?>
            <div class="config-item">
                <i class="fa fa-check-circle"></i>
                <span>Datum-Boost aktiviert</span>
            </div>
        <?php endif; ?>
        
        <?php if ($this->strategy['enable_subject_boost']): ?>
            <div class="config-item">
                <i class="fa fa-check-circle"></i>
                <span>Schlagwort-Boost aktiviert</span>
            </div>
        <?php endif; ?>
        
        <?php if ($this->strategy['enable_availability_boost']): ?>
            <div class="config-item">
                <i class="fa fa-check-circle"></i>
                <span>Verfügbarkeits-Boost aktiviert</span>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="strategy-weighting">
        <strong>Gewichtungsfaktor:</strong> <?=$this->strategy['weighting_factor']?>
    </div>
</div>
```

### Beispiel 4: CSS für RelevancePicker-Styling
```css
/* CSS für RelevancePicker-Integration */
.relevance-strategy-info {
    display: inline-block;
    padding: 4px 8px;
    background: #28a745;
    color: white;
    border-radius: 4px;
    font-size: 12px;
    margin: 5px 0;
}

.strategy-label {
    font-weight: bold;
    margin-right: 5px;
}

.strategy-name {
    font-size: 14px;
}

.relevance-strategy-details {
    margin: 15px 0;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
    border-left: 4px solid #28a745;
}

.relevance-strategy-details h4 {
    color: #333;
    margin-bottom: 15px;
    font-size: 16px;
}

.strategy-configuration {
    margin-bottom: 15px;
}

.config-item {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
}

.config-item i {
    color: #28a745;
    margin-right: 8px;
    width: 16px;
}

.strategy-weighting {
    padding: 10px;
    background: #e9ecef;
    border-radius: 4px;
    text-align: center;
    font-weight: bold;
}

/* KatalogPlus-spezifische Styling */
.katalogplus-strategy {
    background: #007bff;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    margin-left: 5px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .relevance-strategy-details {
        margin: 10px 0;
        padding: 10px;
    }
    
    .relevance-strategy-info {
        display: block;
        text-align: center;
        margin: 5px 0;
    }
}
```

## Troubleshooting

### Häufige Probleme

**Problem**: Relevanz-Strategien werden nicht angewendet
- **Lösung**: Überprüfen Sie, ob das RelevancePicker-Modul im Qcovery Core aktiviert ist
- **Lösung**: Stellen Sie sicher, dass die VuFind-Konfiguration korrekt ist
- **Lösung**: Überprüfen Sie die Relevanz-Strategie-Konfiguration

**Problem**: Relevanz-Strategien funktionieren nicht
- **Lösung**: Überprüfen Sie die Strategie-Konfiguration
- **Lösung**: Stellen Sie sicher, dass die KatalogPlus-Integration aktiviert ist
- **Lösung**: Überprüfen Sie die Performance-Einstellungen

**Problem**: KatalogPlus-Features funktionieren nicht
- **Lösung**: Überprüfen Sie `enable_katalogplus_integration = true`
- **Lösung**: Stellen Sie sicher, dass die SUB Hamburg-Features aktiviert sind
- **Lösung**: Überprüfen Sie die KatalogPlus-spezifischen Einstellungen

**Problem**: Performance-Probleme mit Relevanz-Strategien
- **Lösung**: Aktivieren Sie `enable_strategy_caching` in der Konfiguration
- **Lösung**: Reduzieren Sie `max_concurrent_strategies`
- **Lösung**: Überprüfen Sie die Batch-Verarbeitung

### Debugging
Aktivieren Sie das VuFind-Logging für detaillierte Informationen:
```ini
[Logging]
error_log = /path/to/vufind/logs/error.log
debug = true
```

## Technische Details

### Abhängigkeiten
- VuFind 7.0 oder höher
- PHP 7.4 oder höher
- Laminas Framework (ehemals Zend Framework)
- Solr-Index mit korrekt konfigurierten Relevanz-Feldern
- JavaScript-fähiger Browser für erweiterte Funktionalität

### Dateistruktur
```
qcovery/core/
├── module/
│   └── RelevancePicker/            # RelevancePicker-Modul
│       ├── config/
│       │   └── module.config.php   # Modul-Konfiguration
│       ├── src/
│       │   └── RelevancePicker/
│       │       ├── Service/         # Relevanz-Strategie-Services
│       │       ├── View/            # View-Helper
│       │       ├── Strategy/        # Relevanz-Strategien
│       │       └── Config/          # Konfigurationsklassen
│       └── theme/
│           ├── css/
│           │   └── relevancepicker.css  # Styling
│           ├── js/
│           │   └── relevancepicker.js   # JavaScript-Funktionalität
│           └── templates/
│               └── relevancepicker/     # Template-Dateien
```

### Routing
Das Modul erweitert bestehende VuFind-Routen:
- Erweitert `/Search/Results` mit Relevanz-Strategie-Informationen
- Erweitert `/Record/[ID]` mit Relevanz-Strategie-Details
- Fügt `/Relevance/Strategy/[ID]` für Relevanz-Strategie-Informationen hinzu
- Keine neuen Routen, sondern Erweiterung bestehender Funktionalität

### Relevanz-Integration
Das Modul integriert sich nahtlos in das bestehende VuFind-Suchsystem:
- Erweitert bestehende Suchergebnisse
- Fügt Relevanz-Strategie-Informationen zu allen Medien hinzu
- Ermöglicht die Auswahl verschiedener Relevanz-Strategien
- Optimiert die Performance durch intelligentes Caching


## Support und Weiterentwicklung

- **GitHub Repository**: [https://github.com/qcovery/core/tree/master/module/RelevancePicker](https://github.com/qcovery/core/tree/master/module/RelevancePicker)
- **Qcovery**: [https://www.qcovery.de](https://www.qcovery.de)


---

*Diese Dokumentation richtet sich an VuFind-Administratoren und Bibliotheksmitarbeitende mit technischem Verständnis. Für Entwickler-Informationen siehe die Inline-Kommentare im Code.*
