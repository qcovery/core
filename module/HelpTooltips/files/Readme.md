# HelpTooltips

## Template snippets
Include the following snippets in your templates:

### Menu entry
```
<!--Module HelpTooltips-->
<?=$this->render('helptooltips/menu.phtml', []) ?>
<!--Module HelpTooltips-->
```

### Generate HTML for tooltips
```
  <!--Module HelpTooltips-->
  <?=$this->render('helptooltips/helptooltips.phtml', ['context' => 'result list']) ?>
  <!--Module HelpTooltips-->
```
The value of "context" selects the tooltips from HelpTooltips.ini with the same value. Use 'all' to display all tooltips.

## CSS

Overwrite the following classes to match our design:

```
.helpTooltipLink {
    /* margin-top: ...px; - overwrite in library theme */
}
```

```
#btn-help:hover {
    /* color: #...;  - overwrite in library theme */
}
```

## Help tooltips
Define your help tooltips in HelpTooltips.ini in the VuFind config directory. See examples in /module/HelpTooltips/files/HelpTooltips.ini