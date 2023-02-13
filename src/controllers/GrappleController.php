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
	
	
	protected array|bool|int $allowAnonymous = ['index', 'handle-webhook', 'get-plugins'];
	
	public $enableCsrfValidation = false;
	
	// Public Methods
	// =========================================================================

	public function actionGetData(): Response
	{
		$data = ['data' => 'Some sample data'];

		return $this->asJson($data);
	}
	
	public function actionToggleTask(): Response
	{
		$file = Craft::getAlias('@storage/logs/pinecone.log');
		// $log = date('Y-m-d H:i:s').json_encode(Craft::$app->request->getBodyParams())." Running... \n";
		// \craft\helpers\FileHelper::writeToFile($file, $log, ['append' => true]);
		$complete = Craft::$app->request->getRequiredBodyParam('complete');
		$taskId = Craft::$app->request->getRequiredBodyParam('taskid');
		$task = \craft\elements\Entry::find()
		->id($taskId)
		->one();
		
		if (!$task) {
			throw new BadRequestHttpException('Invalid task ID: ' . $taskId);
			return 'No task!';
		} else {
			$log = date('Y-m-d H:i:s')." Task Title: ".$task->title." ".$complete." \n";
			\craft\helpers\FileHelper::writeToFile($file, $log, ['append' => true]);
			if ($complete == 'true') {
				$task->setFieldValue('taskStatus', 'open'); 
			} else {
				$task->setFieldValue('taskStatus', 'complete'); 	
			}
			
			Craft::$app->getElements()->saveElement($task);
		}

	
		
		
			
		return $this->asJson([
			'success' => true,
		]);
	}
	
	public function actionCreateInvoice(): Response
	{
		$file = Craft::getAlias('@storage/logs/pinecone.log');
		// $log = date('Y-m-d H:i:s').json_encode(Craft::$app->request->getBodyParams())." Running... \n";
		// \craft\helpers\FileHelper::writeToFile($file, $log, ['append' => true]);
		$projectId = Craft::$app->request->getRequiredBodyParam('projectId');
		$relatedProjectArray = \craft\elements\Entry::find()->id($projectId);
		$project = \craft\elements\Entry::find()
		->id($projectId)
		->one();
		
		if (!$project) {
			throw new BadRequestHttpException('Invalid project ID: ' . $projectId);
			return 'No task!';
		} else {
			$log = date('Y-m-d H:i:s')." Generating project invoice: ".$project->title."\n";
			\craft\helpers\FileHelper::writeToFile($file, $log, ['append' => true]);
			
			// Check if there is an open invoice to add line items to, if not create one
			
			$relatedInvoice = \craft\elements\Entry::find()
				->sectionId(5)
				->relatedTo($relatedProjectArray)
				->invoiceStatus('draft')
				->one();
				
				if (!$relatedInvoice) {
					$newInvoice = new Entry([
						'sectionId' => 5,
						'typeId' => 5
					]);
				} else {
					$log = date('Y-m-d H:i:s')." Open invoice found \n";
					\craft\helpers\FileHelper::writeToFile($file, $log, ['append' => true]);
					$newInvoice = $relatedInvoice;
					if (array_key_exists(0,$relatedInvoice->lineItems)){
						$lineItems[] = $relatedInvoice->lineItems[0];	
					}
					
				}
			
			
			$lineItems=[];
			$client = $project->client;
			$relatedTasks = $project->relatedTasks;
			foreach ($relatedTasks as $task ){
				
				if (($task->taskStatus == 'complete' || $task->taskStatus == 'archived') && $task->invoiced != true && $task->lineItems != null) {
					$lineItems[] = $task->lineItems[0];
					$task->setFieldValue('invoiced',true);
					Craft::$app->elements->saveElement($task);
				}
			}
			
			
			
			$newInvoice->setFieldValues([
				'lineItems' => $lineItems,
				'client' => $client,
				'project' => $relatedProjectArray
			]);
			
			$savedInvoice = Craft::$app->elements->saveElement($newInvoice);
			return $this->asJson([
				'success' => true,
				'id' => $newInvoice->id,
				'editUrl' => $newInvoice->cpEditUrl
			]);
			
		}
	
	
		
		
			
	
	}
	
	
	public function actionHandleWebhook()
	{
		// $signature = Craft::$app->getRequest()->getHeaders()->get('Stripe-Signature');
		// $secret = StripeWebhooks::$plugin->getSettings()->signingSecret;
		$payload = json_decode(Craft::$app->getRequest()->getRawBody());
			
			if (!$payload) {
				return $this->asJson([
				'success' => False
				]);
			} else {
				$subject = isset($payload->Subject) ? $payload->Subject : '';
				$from = isset($payload->From) ? $payload->From : '';
				$fromName = isset($payload->FromName) ? $payload->FromName: '';
				$to = isset($payload->OriginalRecipient) ? $payload->OriginalRecipient : '';
				$messageId = isset($payload->MessageID) ? $payload->MessageID : '';
				$date = isset($payload->Date) ? $payload->Date : '';
				$textBody = isset($payload->TextBody) ? $payload->TextBody : '';
				$strippedTextReply = isset($payload->StrippedTextReply) ? $payload->StrippedTextReply : '';
				$htmlBody = isset($payload->HtmlBody) ? $payload->HtmlBody : '';
				$attachments = isset($payload->Attachments) ? $payload->Attachments : '';
				
				
				// Check if this to the help ticket address
				if (str_contains($to,'help') {
					
					// Check if related ticket. Tickets are related by having the email address they use 
					// include the first message Message ID, eg: help+iujnds98fsdn89sdf@highland.tools
					$relatedTicket = \craft\elements\Entry::find()
					->sectionId(6)
					->ticketId($to)
					->one();
					
					if (!$relatedTicket) {
						$newTicket = new Entry([
							'sectionId' => 6,
							'typeId' => 6
						]);
						$newTicket->setFieldValues([
							// Ticket ID is also the unique email address for this ticket
							'ticketId' => explode("@",$to)[0].'+'.$messageId.'@highland.tools'
						]);
						$newTicket->title = $subject;
						
						// Notify support
						Craft::$app
							->getMailer()
							->compose()
							->setTo('sid@madebyhighland.com')
							->setFrom([$newTicket->ticketId => 'Highland Support'])
							->setSubject('New support ticket')
							->setHtmlBody('New ticket on highland.tools!')
							->send();
							
						// Autoreply to user
						Craft::$app
							->getMailer()
							->compose()
							->setTo($from)
							->setFrom([$newTicket->ticketId => 'Highland Support'])
							->setSubject('Re: '.$subject)
							->setHtmlBody('We\'ll be in touch as soon as possible. Replying to this email will update this support ticket. Thanks!')
							->send();
						
					} else {
						$newTicket = $relatedTicket;
						
						
						
					}
					$sortOrder = (clone $newTicket->messages)->anyStatus()->ids();
					$sortOrder[] = 'new:1';
					// $explodedDate = explode(" ",$date);
					// $dateString=$explodedDate[2].' '.$explodedDate[1].', '.$explodedDate[3];//.' '.$explodedDate[4];
					$receivedDate = Craft::$app->getFormatter()->asDatetime($date, 'r');
						//Craft::$app->getFormatter()->asDatetime(DateTimeHelper::toIso8601($date),'Y-m-dTH:i');
						
					$newBlock = [
						'type' => 'message',
						'fields' => [
							'from' => $from,
							'fromName' => $fromName,
							'subject' => $subject,
							'htmlBody' => $htmlBody,
							'strippedTextReply' => $strippedTextReply,
							'dateReceived' => [
								'datetime' => date('Y-m-d H:i:s'),
								'timezone' => 'America/Detroit'
							],
							'sender' => 'client'
						],
					];
					$newTicket->setFieldValue('messages', [
						'sortOrder' => $sortOrder,
						'blocks' => [
							'new:1' => $newBlock,
						],
					]);
					
					$newTicket->setFieldValues([
						'ticketStatus' => 'open'
					]);
					
					$savedTicket = Craft::$app->elements->saveElement($newTicket);
						
						
					return $this->asJson([
						'success' => True
						]);
				} 
				// If to does not contain the help string
				else {
					$file = Craft::getAlias('@storage/logs/pinecone.log');
					$log = date('Y-m-d H:i:s')." Non help email message received... \n";
					\craft\helpers\FileHelper::writeToFile($file, $log, ['append' => true]);
					
			}
			

	}
	
	public function actionTicketResponse()
	{

			
			$ticketId = Craft::$app->request->getRequiredBodyParam('ticketid');
			$ticketResponse = Craft::$app->request->getRequiredBodyParam('response');
			$relatedTicket = \craft\elements\Entry::find()
			->sectionId(6)
			->id($ticketId)
			->one();
			if (!$relatedTicket) {
				return $this->asJson([
				'success' => False
				]);
			} else {
				$sortOrder = (clone $relatedTicket->messages)->anyStatus()->ids();
				$sortOrder[] = 'new:1';
					
				$newBlock = [
					'type' => 'message',
					'fields' => [
						'from' => $relatedTicket->ticketId,
						'fromName' => 'Highland Support',
						'subject' => $relatedTicket->title,
						'htmlBody' => $ticketResponse,
						'dateReceived' => [
							'datetime' => date('Y-m-d H:i:s'),
							'timezone' => 'America/Detroit'
						]
					],
				];
				$relatedTicket->setFieldValue('messages', [
					'sortOrder' => $sortOrder,
					'blocks' => [
						'new:1' => $newBlock,
					],
				]);
				
			
				
				$savedTicket = Craft::$app->elements->saveElement($relatedTicket);
				
				
				Craft::$app
				->getMailer()
				->compose()
				->setTo('sid@madebyhighland.com')
				->setFrom([$relatedTicket->ticketId => 'Highland Support'])
				->setSubject('Re: '.$relatedTicket->title)
				->setHtmlBody($ticketResponse)
				->send();
					
				return $this->asJson([
					'success' => True
					]);
			}
			
	
	}
	
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