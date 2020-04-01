<?php declare(strict_types=1);

namespace DavidBadura\GitWebhooks\Event;

class PingEvent extends AbstractEvent
{
	/** @var int */
	public $id;
	/** @var string */
	public $title;
	/** @var string */
	public $description;
	/** @var string */
	public $action;
}
