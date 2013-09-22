<?php
/**
 * Created by JetBrains PhpStorm.
 * User: machado
 * Date: 7/8/12
 * Time: 10:25 PM
 * To change this template use File | Settings | File Templates.
 */
class AssetCompilerCommand extends CConsoleCommand
{

    public function actionAll()
    {
        echo "Compiling all assets\n";
        Yii::app()->assetCompiler->compileAll();
    }

    public function actionJs()
    {
        echo "Compiling js assets\n";
        Yii::app()->assetCompiler->compileAllJsGroups();
    }

    public function actionCss()
    {
        echo "Compiling css assets\n";
        Yii::app()->assetCompiler->compileAllCssGroups();
    }

    public function actionLess()
    {
        echo "Compiling less assets\n";
        Yii::app()->assetCompiler->compileAllLessGroups();
    }

    public function actionGroup($group)
    {
        echo "Compiling assets in group $group\n";
        Yii::app()->assetCompiler->compileGroup($group);
    }
}
