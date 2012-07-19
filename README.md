yii-assetcompiler
=================
Yii extension for js/less asset compilation and management


This extension uses the [Google Closure compiler](https://developers.google.com/closure/compiler/) and [lessphp](http://leafo.net/lessphp/) to compile your assets into combined/optimized versions. The extension can do this compilation on the fly (using the autoCompile/forceCompile configuration parameters) but is designed to be compiled at deployment time using a yii console command. Views and layouts then make reference to the asset groups to bring in either the compiled asset (production mode) or the raw asset components (in developer mode).

The lessphp compiler was chosen because it is compatible with the [Bootstrap](http://twitter.github.com/bootstrap/) framework's usage of LESS.


Dependencies
------------

This extension uses [Google Closure compiler](https://developers.google.com/closure/compiler/) and [lessphp](http://leafo.net/lessphp/). For lessphp, version 0.3.5 or later is required due to its support for compressed css mode.

By default, the extension will look for these packages installed in `protected/vendor/googleclosure` and `protected/vendor/lessphp`, respectively. Simply extract compiler-latest.zip into protected/vendor/googleclosure and lessphp-0.3.5.tar.gz into protected/vendor/lessphp.

If you put these packages in another locaiton, you must manny set the googleClosure* and/or plessc* configuration options to their respective locations (see Configuration Options below ffor details).


Installation
------------

Copy the `extensions` directory to your <yii_app>/protected/extensions directory and the contents of `commands` to your <yii_app>/protected/commands directory.

Edit your main.php configuration file using the Configuration Options/Configuration Example below.


Modes
-----

This extension supports developer mode whereby it renders the HTML to serve up the individual uncompiled files and uses less.js to serve the raw less files as well as a production mode where it serves the compiled/compressed files. By default this extension uses the YII_DEBUG define to know if it is in production mode or developer mode. If you run YII_DEBUG=true in production (you shouldn't be!), you can explicitly set the debugMode => false option. 

When in developer/debug mode, the extension will register the raw javascript components of the group (group types of 'js') and the raw .less files using the appropriate <link> tag and also register the less.js script. 

In production mode (debugMode=>false), the compiled versions of the group are registered with the page.


Configuration Options
---------------------

The following configuration options can be passed as an element in the 'assetCompiler' array to control behavior, override defaults and set paths. Defaults for the option are listed in [] next to the option name.

* `groups` *[]* - This is the heart of the extension, defining what different groups of files you want to combine. More detail in the Groups section below.
* `basePath` *[root of application]* - Path to application on filesystem.
* `baseUrl` *[webroot detected by Yii]* - URL of application.
* `debugMode` *[YII_DEBUG]* - (true/false) Enable debug/developer mode or not, defaults to using YII_DEBUG define.
* `autoCompile` *[false]* - (true/false) Automatically check the timestamp of the output file and if it is older than any of the component file(s), automatically recompile the group. For autoCompile to be affective, you should preload the assetCompiler component in the Yii 'preload' section.
* `forceCompile` *[false]* - (true/false) Recompile the group when it is used everytime to matter the timestamps - useful for debugging or dev mode.
* `lessJsUrl` *[../js/less.min.js]* - URL to the less javascript file, used when in debug mode and rendering a less file. Note: version 1.3.0 or greater is required to support bootstrap.
* `jsRenderPosition` *[CClientScript::POS_HEAD]* - Location to render javascript tags - passed on to clientScript->registerScriptFile() calls.
* `jsCompiler` *[googleclosure]* - Which javascript compiler to use - currently only 'googleclosure' is supported. Others may be supported in the future.
* `googleClosureJavaPath` *[java]* - Path to java program to run google closure compiler.jar. 
* `googleClosureJarFile` *[approot/vendor/googleclosure/compiler.jar]* - Path to google closure compiler.jar file.
* `googleClosureCompilationLevel` *[WHITESPACE_ONLY]* - Google closure compilation level, passed directly to java command. Options are WHITESPACE_ONLY, SIMPLE_OPTIMIZATIONS and ADVANCED_OPTIMIZATIONS. Read the google closure documentation for more detail.
* `lessCompiler` *[plessc]* - Which less compiler to use - currently only 'plessc' is supported. Others may be supported in the future.
* `plesscPath` *[approot/vendor/lessphp/plessc]* - Path to plessc command from the lessphp package.
* `plesscFormat` *[compressed]* - Which output format for plessc, passed directly to plessc command. Options are 'default' and 'compressed'.


Groups
------

Groups are the core part of this extension. 

A group is defined as a name and an array value containing settings. The group name is used later in layout/views in calls to `Yii::app()->assetCompiler->registerAssetGroup()`. A group definition looks like:

```php
	'siteJs' => array(
		'type' => 'js',
		'files' => array('js/script1.js','js/script2.js'),
		'output' => 'js/scripts-min.js',
	),
```

The type is either 'js' for javascript groups or 'less' for less groups. js groups have a configuration parameter called 'files' which is an array to a list of files making up that group. less groups only support a single file, so the parameter is called 'file'. The single less file can include other less files, however (such as how bootstrap uses less). Both group types define where their output will be written by the 'output' option. All paths are relative to the base directory of the Yii application.




Configuration Example
---------------------

The following would go into your Yii 'components' array:

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


Usage In Layouts and Views
--------------------------

From layouts or views, you can call the registerAssetGroup() method just like you would call clientScript->registerScriptFile() to have either the group components (in debug/dev mode) or the compiled asset (in non-debug/dev mode) registered in the page.

```php
Yii::app()->assetCompiler->registerAssetGroup('siteLess');
Yii::app()->assetCompiler->registerAssetGroup(array('bootstrapJs','siteJs'));
```


Compiling Assets From The Console
---------------------------------

The power and design of this extension is in its ability to compile the assets from the command line, ideally during the deploy phase of your application. Doing it this way means no overhead on each request to check file timestamps and possibly recompile during the request. A compilation is done once during deploy and the compiled assets are on disk and served to clients. The (small) downside to this approach is that (unless autoCompile is on) if you edit the raw javascript/less files, the clients will not see the changes. It is good practice to do edits in development, test in staging (with debug/dev mode turned off) and the deploy to production anytime a change is required to the raw files.

To compile assets, make sure the commands/AssetCompilerCommand.php file is in your protected/commands/ directory and run from the protected/ directory:

```
./yiic assetcompiler all
```

You can also choose to compile a specifc group by:

```
./yiic assetcompiler group --group=siteJs
```

or just specific types of asset groups
```
./yiic assetcompiler js
#or
./yiic assetcompiler less
```



