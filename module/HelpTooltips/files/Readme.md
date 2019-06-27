# Include the following snippets in your templates:

##1) Menu entry
```
<!--Module HelpTooltips-->
<?=$this->render('helptooltips/menu.phtml', []) ?>
<!--Module HelpTooltips-->
```

##2) Generate HTML for tooltips
```
  <!--Module HelpTooltips-->
  <?=$this->render('helptooltips/helptooltips.phtml', ['context' => 'result list']) ?>
  <!--Module HelpTooltips-->
```
The value of "context" selects the tooltips from HelpTooltips.ini with the same value. Use 'all' to display all tooltips.
