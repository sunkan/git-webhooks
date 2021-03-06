<?php declare(strict_types=1);

namespace DavidBadura\GitWebhooks\Struct;

class Commit
{
	/** @var string */
	public $id;
	/** @var string */
	public $message;
	/** @var \DateTimeImmutable */
	public $date;
	/** @var User */
	public $author;
}
