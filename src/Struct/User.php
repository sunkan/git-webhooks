<?php declare(strict_types=1);

namespace DavidBadura\GitWebhooks\Struct;

/**
 * @author David Badura <d.a.badura@gmail.com>
 */
class User
{
	/**
	 * @var string
	 */
	public $id;

	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var string
	 */
	public $email;
}