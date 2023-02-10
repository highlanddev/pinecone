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
			
		Event::on(
			Entry::class, 
			Element::EVENT_AFTER_SAVE, 
			function(ModelEvent $e) {
				/* @var Entry $entry */
				$entry = $e->sender;
		
				if (ElementHelper::isDraftOrRevision($entry)) {
					// don’t do anything with drafts or revisions
					return;
				} 
				
				if ($entry->section->handle === 'tasks') {
					$file = Craft::getAlias('@storage/logs/pinecone.log');
					$log = date('Y-m-d H:i:s').' '.$entry->title." saved. \n";
					\craft\helpers\FileHelper::writeToFile($file, $log, ['append' => true]);
					
					// If this is a repeating task then duplicate it with a reference
					if ($entry->repeatInterval != 'none' && $entry->repeatInterval != null && $entry->taskStatus == 'complete' ) {
						$duplicateExists = \craft\elements\Entry::find()->repeatOf($entry->slug)->count();
							if (!$duplicateExists > 0) {
								$entry->taskStatus = 'open';
								$entry->repeatOf = $entry->slug;
								$entry->dueDate = $entry->dueDate->modify('+'.$entry->repeat.' '.$entry->repeatInterval);
								$clonedEntry = Craft::$app->getElements()->duplicateElement($entry);		
							}
						
					}
				}
				
			}
		);
		
		// Register control panel section
	Event::on(
			Cp::class,
			Cp::EVENT_REGISTER_CP_NAV_ITEMS,
			function(RegisterCpNavItemsEvent $event) {
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
			function(RegisterComponentTypesEvent $event) {
				$event->types[] = HighlandText::class;
			}
		);
	}
	

	
	
}

	


