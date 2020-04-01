<?php declare(strict_types=1);

namespace DavidBadura\GitWebhooks\Provider;

use DavidBadura\GitWebhooks\Event\AbstractEvent;
use DavidBadura\GitWebhooks\Event\MergeRequestEvent;
use DavidBadura\GitWebhooks\Event\PushEvent;
use DavidBadura\GitWebhooks\Struct\Commit;
use DavidBadura\GitWebhooks\Struct\Repository;
use DavidBadura\GitWebhooks\Struct\User;
use Psr\Http\Message\ServerRequestInterface;

class BitbucketProvider extends AbstractProvider implements ProviderInterface
{
	public const NAME = 'bitbucket';

	public function create(ServerRequestInterface $request): ?AbstractEvent
	{
		$data = $this->getData($request);
		switch ($request->getHeaderLine('X-Event-Key')) {
			case 'repo:push':
				return $this->createPushEvent($data);
			case 'pull_request':
				return $this->createMergeRequestEvent($data);
			default:
				return null;
		}
	}

	public function support(ServerRequestInterface $request): bool
	{
		return $request->hasHeader('X-Event-Key');
	}

	/**
	 * @param array $data
	 * @return PushEvent
	 */
	private function createPushEvent($data)
	{
		$event = new PushEvent();
		$event->provider = self::NAME;

		$change = $data['push']['changes'][0];

		$event->before = $change['old']['target']['hash'];
		$event->after = $change['new']['target']['hash'];

		//$event->ref = $data['ref'];
		$event->type = $change['new']['type'];
		if ($event->type == PushEvent::TYPE_BRANCH) {
			$event->branchName = $change['new']['name'];
		} else {
			$event->tagName = $change['new']['name'];
		}

		$event->user = $this->createUser($data['actor']);
		$event->repository = $this->createRepository($data['repository']);
		$event->commits = $this->createCommits($change['commits']);

		return $event;
	}

	private function createMergeRequestEvent(array $data): MergeRequestEvent
	{
		$event = new MergeRequestEvent();

		$event->provider = self::NAME;
		$event->id = $data['pullrequest']['id'];
		$event->title = $data['pullrequest']['title'];
		$event->description = $data['pullrequest']['description'];

		$event->targetBranch = $data['pullrequest']['destination']['branch']['name'];
		$event->sourceBranch = $data['pullrequest']['source']['branch']['name'];
		$event->state = $this->pullRequestState($data['pullrequest']);
		$event->createdAt = new \DateTimeImmutable($data['pullrequest']['created_on']);
		$event->updatedAt = new \DateTimeImmutable($data['pullrequest']['updated_on']);

		$user = new User();
		$user->id = $data['actor']['uuid'];
		$user->name = $data['actor']['display_name'];

		$event->user = $user;
		$event->repository = $this->createRepository($data['pullrequest']['destination']['repository']);
		$event->sourceRepository = $this->createRepository($data['pullrequest']['source']['repository']);

		// TODO request data from $data['pull_request']['commits_url']
		$event->lastCommit = new Commit();
		$event->lastCommit->id = $data['pullrequest']['source']['commit']['hash'];

		return $event;
	}

	private function createRepository(array $data): Repository
	{
		$repository = new Repository();
		$repository->id = $data['uuid'];
		$repository->name = $data['name'];
		$repository->namespace = $this->extractNamespace($data['full_name']);
		$repository->description = null;
		$repository->homepage = $data['website'];
		$repository->url = null;

		return $repository;
	}

	protected function createCommit(array $data): Commit
	{
		$commit = new Commit();

		$commit->id = $data['hash'];
		$commit->message = $data['message'];
		$commit->author = $this->createUser($data['author']);

		return $commit;
	}

	private function createUser(array $data): User
	{
		$user = new User();
		$user->id = $data['uuid'];
		$user->name = $data['display_name'];

		return $user;
	}

	private function extractNamespace(string $fullName): string
	{
		$parts = explode('/', $fullName);

		return $parts[0];
	}

	private function pullRequestState(array $pullRequest): string
	{
		if ($pullRequest['state'] == 'OPEN') {
			return MergeRequestEvent::STATE_OPEN;
		}

		if ($pullRequest['state'] == 'MERGED') {
			return MergeRequestEvent::STATE_MERGED;
		}

		return MergeRequestEvent::STATE_CLOSED;
	}
}
