<?php declare(strict_types=1);

namespace DavidBadura\GitWebhooks\Struct;

class Repository
{
	/** @var int */
	public $id;
	/** @var string */
	public $name;
	/** @var string */
	public $namespace;
	/** @var string */
	public $description;
	/** @var string */
	public $homepage;
	/** @var string */
	public $url;
}
