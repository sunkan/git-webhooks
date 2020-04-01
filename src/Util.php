<?php declare(strict_types=1);

namespace DavidBadura\GitWebhooks;

use DavidBadura\GitWebhooks\Event\PushEvent;

class Util
{
	public static function getPushType(string $ref): string
	{
		if (strpos($ref, 'refs/tags/') === 0) {
			return PushEvent::TYPE_TAG;
		}

		if (strpos($ref, 'refs/heads/') === 0) {
			return PushEvent::TYPE_BRANCH;
		}

		throw new \InvalidArgumentException("type not supported");
	}

	public static function getBranchName(string $ref): string
	{
		if (self::getPushType($ref) != PushEvent::TYPE_BRANCH) {
			throw new \InvalidArgumentException("ref isn't a branch");
		}

		return substr($ref, 11);
	}

	public static function getTagName(string $ref): string
	{
		if (self::getPushType($ref) != PushEvent::TYPE_TAG) {
			throw new \InvalidArgumentException("ref isn't a tag");
		}

		return substr($ref, 10);
	}
}