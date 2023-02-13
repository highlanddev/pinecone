<?php
namespace pinecone\controllers;

use Craft;
use craft\web\Controller;
use craft\app\Element;
use yii\web\Response;
use craft\elements\Entry;
use craft\helpers\DateTimeHelper;
use yii\web\HttpException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\UnauthorizedHttpException;
use craft\queue;
use craft\helpers\Queue as QueueHelper;

class GrappleController extends Controller
{
	// Properties
	// =========================================================================
	
	
	protected $allowAnonymous = ['index', 'get-plugins'];
	
	public $enableCsrfValidation = false;
	
	// Public Methods
	// =========================================================================

	
	
	public function actionGetPlugins()
	{	
		
		$key = Craft::$app->getRequest()->getParam('key', '');
		$apiKey = getenv('PINECONE_API_KEY');
		if (empty($key) || empty($apiKey) || $key != $apiKey) {
			throw new HttpException(403, 'Unauthorised API key');
		}
		$queue = Craft::$app->getQueue();
		$failed = $queue->getTotalFailed();
		// $stuck = $queue->getTotalJobs();
		$plugins = Craft::$app->plugins->getAllPluginInfo();
		return $this->asJson([
			'siteUrl' =>getenv('PRIMARY_SITE_URL'),
			'failedJobs' => $failed,
			'pluginData' => $plugins
		]);
	}

	
}