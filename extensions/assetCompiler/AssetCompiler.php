<?php
/**
 * @name AssetCompiler
 * @author Mike Machado <mike@machadolab.com>
 * @version 0.3
 * Yii asset compiler that integrates google closure for javascript compilation/optimization
 * and plessc for less/css integration/compression
 *
 */
class AssetCompiler extends CApplicationComponent
{

    public $basePath;

    public $baseUrl;

    public $debugMode = YII_DEBUG;

    public $groups;

    public $autoCompile = false;
    public $forceCompile = false;


    public $lessJsUrl;

    public $jsRenderPosition = CClientScript::POS_HEAD;

    public $jsCompiler = 'googleclosure';

    public $googleClosureJavaPath;
    public $googleClosureJarFile;
    public $googleClosureCompilationLevel;

    public $lessCompiler = 'plessc';

    public $plesscPath;
    public $plesscFormat;



    /**
     * Initializes the component.
     * @throws CException if the base path does not exist.
     */
    public function init()
    {
        if (!isset($this->basePath))
            $this->basePath = Yii::getPathOfAlias('application').DIRECTORY_SEPARATOR.'..';

        if (!file_exists($this->basePath))
            throw new CException(__CLASS__.': Failed to initialize compiler. Base path does not exist!');

        if (!is_dir($this->basePath))
            throw new CException(__CLASS__.': Failed to initialize compiler. Base path is not a directory!');

        if (!isset($this->baseUrl))
            $this->baseUrl = Yii::app()->getBaseUrl();

        if (!isset($this->lessJsUrl))
            $this->lessJsUrl = '../js/less.min.js';

        if (!isset($this->googleClosureJavaPath))
            $this->googleClosureJavaPath = 'java';

        if (!isset($this->googleClosureJarFile))
            $this->googleClosureJarFile = dirname(__FILE__).DIRECTORY_SEPARATOR.'../../vendor/googleclosure/compiler.jar';

        if (!isset($this->googleClosureCompilationLevel))
            $this->googleClosureCompilationLevel = 'WHITESPACE_ONLY';

        if (!isset($this->plesscPath))
            $this->plesscPath = dirname(__FILE__).DIRECTORY_SEPARATOR.'../../vendor/lessphp/plessc';

        if (!isset($this->plesscFormat))
            $this->plesscFormat = 'default';

        if ($this->forceCompile)
            $this->compileAll();
        elseif ($this->autoCompile && !$this->debugMode)
            $this->compileNeedsUpdate();
    }


    private function checkGroupExists($group)
    {
        if (!isset($this->groups[$group]))
            throw new CException(__CLASS__.': Failed to find group ' . $group . ' in configuration!');
    }


    public function registerAssetGroup($group)
    {

        if (is_array($group))
        {
            foreach ($group as $g)
            {
                $this->registerAssetGroup($g);
            }
        }
        else
        {
            if ($this->debugMode)
                $this->registerAssetGroupRaw($group);
            else
                $this->registerAssetGroupCompiled($group);
        }
    }


    public function registerAssetGroupRaw($group)
    {
        $this->checkGroupExists($group);

        $g = $this->groups[$group];

        if ($g['type'] == 'js')
        {
            foreach ($g['files'] as $f)
            {
                $this->registerFile($f, $g['type']);
            }
        }
        elseif ($g['type'] == 'less')
        {
            $this->registerFile($g['file'], $g['type']);
        }

    }

    public function registerAssetGroupCompiled($group)
    {
        $this->checkGroupExists($group);

        $g = $this->groups[$group];

        $type = $g['type'];
        if ($type == 'less')
            $type = 'css'; // less files become css after compilation

        $this->registerFile($g['output'], $type);
    }

    public function registerFile($file, $type)
    {
        if ($type == 'js')
        {
            Yii::app()->clientScript->registerScriptFile($this->baseUrl.'/'.$file, $this->jsRenderPosition);
        }
        elseif ($type == 'css')
        {
            Yii::app()->clientScript->registerCssFile($this->baseUrl.'/'.$file);
        }
        elseif ($type == 'less')
        {
            Yii::app()->clientScript->registerScriptFile($this->lessJsUrl);
            Yii::app()->clientScript->registerLinkTag('stylesheet/less','text/css',$this->baseUrl.'/'.$file);
        }
        else
        {
            throw new CException(__CLASS__.': Failed to register asset group raw. Unknown group type ' . $type);
        }
    }


    public function compileNeedsUpdate()
    {

        foreach ($this->groups as $group=>$g)
        {
            if ($g['type'] == 'js' && $this->needsUpdateJsGroup($group))
                $this->compileJsGroup($group);
            elseif ($g['type'] == 'less' && $this->needsUpdateLessGroup($group))
                $this->compileLessGroup($group);
        }
    }

    public function compileGroup($group)
    {
        $this->checkGroupExists($group);

        $g = $this->groups[$group];

        if ($g['type'] == 'js')
            $this->compileJsGroup($group);
        elseif ($g['type'] == 'less')
            $this->compileLessGroup($group);
        else
            throw new CException(__CLASS__.': Group has unknown type ' . $g['type']);
    }

    public function compileAll()
    {
        $this->compileAllJsGroups();
        $this->compileAllLessGroups();
    }

    public function compileAllJsGroups()
    {
        foreach ($this->groups as $group=>$g)
            if ($g['type'] == 'js')
                $this->compileJsGroup($group);
    }

    public function compileAllLessGroups()
    {
        foreach ($this->groups as $group=>$g)
            if ($g['type'] == 'less')
                $this->compileLessGroup($group);
    }


    private function needsUpdateJsGroup($group)
    {
        $g = $this->groups[$group];

        $needsUpdate = false;

        $outputTs = $this->getLastModified($this->basePath.DIRECTORY_SEPARATOR.$g['output']);
        foreach ($g['files'] as $f)
        {
            $fileTs = $this->getLastModified($this->basePath.DIRECTORY_SEPARATOR.$f);
            if ($outputTs < $fileTs)
                $needsUpdate = true;
        }

        return $needsUpdate;
    }

    public function compileJsGroup($group)
    {

        if ($this->jsCompiler == 'googleclosure')
            $this->compileJsGroupGoogleClosure($group);
        else
            throw new CException(__CLASS__.': Unknown jsCompiler ' . $this->jsCompiler);
    }

    private function compileJsGroupGoogleClosure($group)
    {
        $g = $this->groups[$group];
        $cmd = $this->googleClosureJavaPath.' -jar '.$this->googleClosureJarFile;
        foreach ($g['files'] as $file)
            $cmd .= ' --js '.$this->basePath.DIRECTORY_SEPARATOR.$file;
        $cmd .= ' --js_output_file '.$this->basePath.DIRECTORY_SEPARATOR.$g['output'];
        $cmd .= ' --compilation_level '.$this->googleClosureCompilationLevel;
        //echo "SYSTEM CMD: " . $cmd . "\n";
        exec($cmd);
    }

    private function needsUpdateLessGroup($group)
    {
        $g = $this->groups[$group];

        $needsUpdate = false;

        $outputTs = $this->getLastModified($this->basePath.DIRECTORY_SEPARATOR.$g['output']);
        $fileTs = $this->getLastModified($this->basePath.DIRECTORY_SEPARATOR.$g['file']);

        if ($outputTs < $fileTs)
            $needsUpdate = true;

        return $needsUpdate;
    }


    public function compileLessGroup($group)
    {
        if ($this->lessCompiler == 'plessc')
            $this->compileLessGroupPlessc($group);
        else
            throw new CException(__CLASS__.': Unknown cssCompiler ' . $this->lessCompiler);
    }

    private function compileLessGroupPlessc($group)
    {
        $g = $this->groups[$group];

        $cmd = $this->plesscPath.' -f='.$this->plesscFormat;
        $cmd .= ' '.$this->basePath.DIRECTORY_SEPARATOR.$g['file'];
        $cmd .= ' '.$this->basePath.DIRECTORY_SEPARATOR.$g['output'];
        //echo "SYSTEM CMD: " . $cmd . "\n";
        exec($cmd);
    }


    /**
     * Returns the last modified for a specific path.
     * @param string $path the path.
     * @return integer the last modified (as a timestamp).
     */
    protected function getLastModified($path)
    {
        if (!file_exists($path))
            return 0;
        else
        {
            if (is_file($path))
            {
                $stat = stat($path);
                return $stat['mtime'];
            }
            else
            {
                $lastModified = null;

                /** @var Directory $dir */
                $dir = dir($path);
                while ($entry = $dir->read())
                {
                    if (strpos($entry, '.') === 0)
                        continue;

                    $path .= '/'.$entry;

                    if (is_dir($path))
                        $modified = $this->getLastModified($path);
                    else
                    {
                        $stat = stat($path);
                        $modified = $stat['mtime'];
                    }

                    if (isset($lastModified) || $modified > $lastModified)
                        $lastModified = $modified;
                }

                return $lastModified;
            }
        }
    }

}
