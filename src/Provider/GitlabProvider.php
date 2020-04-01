<?php declare(strict_types=1);

namespace DavidBadura\GitWebhooks\Provider;

use DavidBadura\GitWebhooks\Event\AbstractEvent;
use DavidBadura\GitWebhooks\Event\IssueEvent;
use DavidBadura\GitWebhooks\Event\MergeRequestEvent;
use DavidBadura\GitWebhooks\Event\PushEvent;
use DavidBadura\GitWebhooks\Struct\Commit;
use DavidBadura\GitWebhooks\Struct\Repository;
use DavidBadura\GitWebhooks\Struct\User;
use DavidBadura\GitWebhooks\Util;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @author David Badura <d.a.badura@gmail.com>
 */
class GitlabProvider extends AbstractProvider implements ProviderInterface
{
	const NAME = 'gitlab';

	public function create(ServerRequestInterface $request): ?AbstractEvent
	{
		$data = $this->getData($request);

		if (!$data) {
			return null;
		}

		switch ($request->getHeaderLine('X-Gitlab-Event')) {
			case 'Push Hook':
			case 'Tag Push Hook':
				return $this->createPushEvent($data);
			case 'Issue Hook':
				return $this->createIssueEvent($data);
			case 'Merge Request Hook':
				return $this->createMergeRequestEvent($data);
			default:
				return null;
		}
	}

	public function support(ServerRequestInterface $request): bool
	{
		return $request->hasHeader('X-Gitlab-Event');
	}

	private function createIssueEvent(array $data): IssueEvent
	{
		// todo
	}

	private function createMergeRequestEvent(array $data): MergeRequestEvent
	{
		$event = new MergeRequestEvent();
		$event->provider = self::NAME;
		$event->id = $data['object_attributes']['iid'];
		$event->title = $data['object_attributes']['title'];
		$event->description = $data['object_attributes']['description'];
		$event->targetBranch = $data['object_attributes']['target_branch'];
		$event->sourceBranch = $data['object_attributes']['source_branch'];
		$event->state = $data['object_attributes']['state'];
		$event->createdAt = new \DateTimeImmutable($data['object_attributes']['created_at']);
		$event->updatedAt = new \DateTimeImmutable($data['object_attributes']['updated_at']);

		$user = new User();
		$user->id = $data['object_attributes']['author_id'];
		$user->name = $data['user']['name'];

		$targetRepository = new Repository();
		$targetRepository->id = $data['object_attributes']['target_project_id'];
		$targetRepository->name = $data['object_attributes']['target']['name'];
		$targetRepository->namespace = $data['object_attributes']['target']['namespace'];
		$targetRepository->url = $data['object_attributes']['target']['ssh_url'];

		$sourceProject = new Repository();
		$sourceProject->id = $data['object_attributes']['source_project_id'];
		$sourceProject->name = $data['object_attributes']['source']['name'];
		$sourceProject->namespace = $data['object_attributes']['source']['namespace'];
		$sourceProject->url = $data['object_attributes']['source']['ssh_url'];

		$event->user = $user;
		$event->repository = $targetRepository;
		$event->sourceRepository = $sourceProject;
		$event->lastCommit = $this->createCommit($data['object_attributes']['last_commit']);

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
		$user->id = $data['user_id'];
		$user->name = $data['user_name'];

		if (isset($data['user_email'])) {
			$user->email = $data['user_email'];
		}

		$repository = new Repository();
		$repository->id = $data['project_id'];
		$repository->name = $data['project']['name'];
		$repository->namespace = $data['project']['namespace'];
		$repository->description = $data['project']['description'];
		$repository->homepage = $data['project']['homepage'];
		$repository->url = $data['project']['url'];

		$event->user = $user;
		$event->repository = $repository;
		$event->commits = $this->createCommits($data['commits']);

		$event->type = Util::getPushType($event->ref);

		if ($event->type == PushEvent::TYPE_BRANCH) {
			$event->branchName = Util::getBranchName($event->ref);
		}
		else {
			$event->tagName = Util::getTagName($event->ref);
		}

		return $event;
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
}