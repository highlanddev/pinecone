<?php

namespace pinecone;

use Craft;
use craft\base\Element;
use craft\elements\Entry;
use craft\events\ModelEvent;
use craft\helpers\ElementHelper;
use yii\base\Event;
use craft\events\RegisterCpNavItemsEvent;
use craft\web\twig\variables\Cp;


use pinecone\fields\HighlandText;
use pinecone\services\addressFromLatLng;
use pinecone\twig\PineconeExtension;
use pinecone\twig\PineconeMapExtension;

use craft\services\Fields;
use craft\events\RegisterComponentTypesEvent;

class Plugin extends \craft\base\Plugin
{
    public function init()
    {
        $this->_registerCustomFieldTypes();

        // Define a custom alias named after the namespace
        Craft::setAlias('@pinecone', __DIR__);

        // Set the controllerNamespace based on whether this is a console or web request
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->controllerNamespace = 'pinecone\\console\\controllers';
        } else {
            $this->controllerNamespace = 'pinecone\\controllers';
        }

        parent::init();

        // Register extensions
        Craft::$app->view->registerTwigExtension(new PineconeExtension());
        // Only register this extension if SimpleMap is installed
        try {
            $mapsplugin = Craft::$app->plugins->getPlugin('simplemap');
           if ($mapsplugin && Craft::$app->plugins->getPluginInfo('simplemap')['isInstalled']) {
               
               Craft::$app->view->registerTwigExtension(new PineconeMapExtension());
           }
        } catch (Exception $e) {
           echo $e;
        } 
        


        // Register control panel section
        Event::on(
            Cp::class,
            Cp::EVENT_REGISTER_CP_NAV_ITEMS,
            function (RegisterCpNavItemsEvent $event) {
                $event->navItems[] = [
                    'url' => 'pinecone',
                    'label' => 'Pinecone',
                    'icon' => '@pinecone/icon-bw.svg',
                ];
            }
        );
    }

    // Function to register a field type, called above
    private function _registerCustomFieldTypes()
    {
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = HighlandText::class;
            }
        );
    }
}
