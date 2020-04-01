<?php declare(strict_types=1);

namespace DavidBadura\GitWebhooks\Event;

class IssueEvent extends AbstractEvent
{
	public const ACTION_OPEN = 'open';
	public const ACTION_CLOSE = 'close';

	/** @var int */
	public $id;
	/** @var string */
	public $title;
	/** @var string */
	public $description;
	/** @var string */
	public $action;
}
