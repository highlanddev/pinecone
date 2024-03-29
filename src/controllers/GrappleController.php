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
use ether\simplemap\services\GeoService;

class GrappleController extends Controller
{
    // Properties
    // =========================================================================


    protected array|bool|int $allowAnonymous = ['index', 'get-plugins'];

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
        $updates = Craft::$app->updates->getUpdates(true);
        $craft = [
            'edition' => Craft::$app->getEditionName(),
            'licensedEdition' => Craft::$app->getLicensedEditionName(),
            'info' => Craft::$app->getInfo(),
            'devMode' => Craft::$app->config->general->devMode
        ];

        return $this->asJson([
            'siteUrl' =>getenv('PRIMARY_SITE_URL'),
            'failedJobs' => $failed,
            'pluginData' => $plugins,
            'updates' => $updates,
            'craft' => $craft
        ]);
    }


    public function addressFromLatLng(string $address, string $country = null): ?array
    {
        try {
            return GeoService::addressFromLatLng($address, $country);
        } catch (Exception $e) {
            Craft::error($e->getMessage(), 'simplemap');

            return [
                'lat' => '',
                'lng' => '',
            ];
        }
    }
}
