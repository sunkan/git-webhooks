<?php declare(strict_types=1);

namespace DavidBadura\GitWebhooks\Provider;

use DavidBadura\GitWebhooks\Event\AbstractEvent;
use DavidBadura\GitWebhooks\Event\MergeRequestEvent;
use DavidBadura\GitWebhooks\Event\PingEvent;
use DavidBadura\GitWebhooks\Event\PushEvent;
use DavidBadura\GitWebhooks\Struct\Commit;
use DavidBadura\GitWebhooks\Struct\Repository;
use DavidBadura\GitWebhooks\Struct\User;
use DavidBadura\GitWebhooks\Util;
use Psr\Http\Message\ServerRequestInterface;

class GithubProvider extends AbstractProvider implements ProviderInterface
{
	public const NAME = 'github';

	public function create(ServerRequestInterface $request): ?AbstractEvent
	{
		$data = $this->getData($request);
		switch ($request->getHeaderLine('X-Github-Event')) {
			case 'ping':
				return $this->createPingEvent($data);
			case 'push':
				return $this->createPushEvent($data);
			case 'pull_request':
				return $this->createMergeRequestEvent($data);
			default:
				return null;
		}
	}

	public function support(ServerRequestInterface $request): bool
	{
		return $request->hasHeader('X-GitHub-Event');
	}

	private function createPingEvent(array $data): PingEvent
	{
		$event = new PingEvent();
		$event->provider = self::NAME;
		$event->repository = $this->createRepository($data['repository']);

		return $event;
	}

	private function createPushEvent(array $data): PushEvent
	{
		$event = new PushEvent();
		$event->provider = self::NAME;
		$event->before = $data['before'];
		$event->after = $data['after'];
		$event->ref = $data['ref'];

		$user = new User();
		$user->id = $data['sender']['id'];
		$user->name = $data['pusher']['name'];

		if (isset($data['pusher']['email'])) {
			$user->email = $data['pusher']['email'];
		}

		$event->user = $user;
		$event->repository = $this->createRepository($data['repository']);
		$event->commits = $this->createCommits($data['commits']);

		if (!$event->commits and $data['head_commit']) {
			$event->commits[] = $this->createCommit($data['head_commit']);
		}

		$event->type = Util::getPushType($event->ref);

		if ($event->type == PushEvent::TYPE_BRANCH) {
			$event->branchName = Util::getBranchName($event->ref);
		}
		else {
			$event->tagName = Util::getTagName($event->ref);
		}

		return $event;
	}

	private function createMergeRequestEvent(array $data): MergeRequestEvent
	{
		$event = new MergeRequestEvent();

		$event->provider = self::NAME;
		$event->id = $data['pull_request']['id'];
		$event->title = $data['pull_request']['title'];
		$event->description = $data['pull_request']['body'];

		$event->targetBranch = $data['pull_request']['base']['ref'];
		$event->sourceBranch = $data['pull_request']['head']['ref'];
		$event->state = $this->pullRequestState($data['pull_request']);
		$event->createdAt = new \DateTimeImmutable($data['pull_request']['created_at']);
		$event->updatedAt = new \DateTimeImmutable($data['pull_request']['updated_at']);

		$user = new User();
		$user->id = $data['pull_request']['user']['id'];
		$user->name = $data['pull_request']['user']['login'];

		$event->user = $user;
		$event->repository = $this->createRepository($data['pull_request']['base']['repo']);
		$event->sourceRepository = $this->createRepository($data['pull_request']['head']['repo']);

		// TODO request data from $data['pull_request']['commits_url']
		$event->lastCommit = new Commit();
		$event->lastCommit->id = $data['pull_request']['head']['sha'];

		return $event;
	}

	private function createRepository(array $data): Repository
	{
		$repository = new Repository();

		$repository->id = $data['id'];
		$repository->name = $data['name'];
		$repository->description = $data['description'];
		$repository->namespace = $this->extractNamespace($data['full_name']);
		$repository->url = $data['ssh_url'];
		$repository->homepage = $data['html_url'];

		return $repository;
	}

	protected function createCommit(array $data): Commit
	{
		$commit = new Commit();

		$commit->id = $data['id'];
		$commit->message = $data['message'];
		$commit->date = new \DateTimeImmutable($data['timestamp']);

		$user = new User();
		$user->name = $data['author']['name'];
		$user->email = $data['author']['email'];

		$commit->author = $user;

		return $commit;
	}

	private function extractNamespace(string $fullName): string
	{
		$parts = explode('/', $fullName);

		return $parts[0];
	}

	private function pullRequestState(array $pullRequest): string
	{
		if ($pullRequest['state'] === 'open') {
			return MergeRequestEvent::STATE_OPEN;
		}

		if ($pullRequest['merged_at']) {
			return MergeRequestEvent::STATE_MERGED;
		}

		return MergeRequestEvent::STATE_CLOSED;
	}
}
