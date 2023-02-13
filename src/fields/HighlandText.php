<?php

namespace pinecone\fields;
use craft\fields\PlainText;

class HighlandText extends PlainText {
	public function init(): void
	{
		parent::init();
	}
	
	public static function displayName(): string
	{
		return "Highland Text";
	}
}