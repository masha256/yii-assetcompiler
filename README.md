yii-assetcompiler
=================

Yii extension for js/less asset compilation and management


This extension uses the [Google Closure compiler](https://developers.google.com/closure/compiler/) and [lessphp](http://leafo.net/lessphp/) to compile your assets into combined/optimized versions. The extension can do this compilation on the fly (using the autoCompile/forceCompile configuration parameters) but is designed to be ran at deployment time using a yii console command.

This extension supports developer mode whereby it renders the HTML to serve up the individual uncompiled files and uses less.js to serve the raw less files as well as a production mode where it serves the compiled/compressed files.





Configuration example:

```php
            'assetCompiler'=>array(
                'class' => 'ext.assetCompiler.AssetCompiler',
                'lessJsUrl' => '/app/js/less-1.3.0.min.js',
                'groups' => array(
                    'siteJs' => array(
                        'type' => 'js',
                        'files' => array('js/script1.js','js/script2.js'),
                        'output' => 'js/scripts-min.js',
                    ),
                    'bootstrapJs' => array(
                        'type' => 'js',
                        'files' => array('js/bootstrap-transition.js','js/bootstrap-tooltip.js',
                            'js/bootstrap-tab.js','js/bootstrap-modal.js','js/bootstrap-alert.js',
                            'js/bootstrap-button.js','js/bootstrap-popover.js','js/bootstrap-dropdown.js'),
                        'output' => 'js/bootstrap-min.js',
                    ),
                    'siteLess' => array(
                        'type' => 'less',
                        'file' => 'css/bootstrap/myapp.less',
                        'output' => 'css/style-min.css',
                    ),
                ),
            ),
```


Usage:

```php
Yii::app()->assetCompiler->registerAssetGroup('siteLess');
Yii::app()->assetCompiler->registerAssetGroup(array('bootstrapJs','siteJs'));
```
