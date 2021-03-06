<?php declare(strict_types=1);

namespace DavidBadura\GitWebhooks\Event;

use DavidBadura\GitWebhooks\Struct\Repository;
use DavidBadura\GitWebhooks\Struct\User;

abstract class AbstractEvent
{
	/** @var string */
	public $provider;
	/** @var User */
	public $user;
	/** @var Repository */
	public $repository;
}
