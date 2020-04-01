<?php declare(strict_types=1);

namespace DavidBadura\GitWebhooks\Event;

use DavidBadura\GitWebhooks\Struct\Commit;
use DavidBadura\GitWebhooks\Struct\Repository;

class MergeRequestEvent extends AbstractEvent
{
	public const STATE_OPEN = 'opened';
	public const STATE_MERGED = 'merged';
	public const STATE_CLOSED = 'closed';

	/** @var int */
	public $id;
	/** @var string */
	public $title;
	/** @var string */
	public $description;
	/** @var Repository */
	public $sourceRepository;
	/** @var string */
	public $targetBranch;
	/** @var string */
	public $sourceBranch;
	/** @var string */
	public $state;
	/** @var Commit */
	public $lastCommit;
	/** @var \DateTimeImmutable */
	public $createdAt;
	/** @var \DateTimeImmutable */
	public $updatedAt;
}
