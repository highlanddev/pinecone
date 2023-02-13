<?php
namespace pinecone\console\controllers;

use Craft;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;



class WrestleController extends Controller
{
	// Properties
	// =========================================================================

	
	public $enableCsrfValidation = false;

	// Public Methods
	// =========================================================================

	
	
	public function actionGetPlugins(): int 
		{
			$plugins = Craft::$app->plugins->getAllPluginInfo();
			$file = Craft::getAlias('@storage/../web/logs/pinecone-plugins.json');
			$log = json_encode($plugins);
			\craft\helpers\FileHelper::writeToFile($file, $log);
			return ExitCode::OK;
		}
	
	
}