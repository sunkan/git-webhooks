<?php declare(strict_types=1);

namespace DavidBadura\GitWebhooks\Provider;

use DavidBadura\GitWebhooks\Struct\Commit;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractProvider
{
	protected function getData(ServerRequestInterface $request): array
	{
		return $request->getParsedBody();
	}

	/**
	 * @return Commit[]
	 */
	protected function createCommits(array $data)
	{
		$result = [];

		foreach ($data as $row) {
			$result[] = $this->createCommit($row);
		}

		return $result;
	}

	abstract protected function createCommit(array $data): Commit;
}