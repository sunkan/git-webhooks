<?php declare(strict_types=1);

namespace DavidBadura\GitWebhooks\Provider;

use DavidBadura\GitWebhooks\Event\MergeRequestEvent;
use DavidBadura\GitWebhooks\Event\PushEvent;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class BitbucketProviderTest extends TestCase
{
	/** @var Psr17Factory */
	private $requestFactory;

	protected function setUp(): void
	{
		$this->requestFactory = new Psr17Factory();
	}

	public function testSupport()
	{
		$request = $this->createRequest('foo');

		$provider = new BitbucketProvider();

		$this->assertTrue($provider->support($request));
	}

	public function testNoSupport()
	{
		$request = $this->requestFactory->createServerRequest('POST', '', []);

		$provider = new BitbucketProvider();

		$this->assertFalse($provider->support($request));
	}

	public function testPush()
	{
		$request = $this->createRequest('repo:push', __DIR__ . '/_files/bitbucket/push.json');

		$provider = new BitbucketProvider();
		/** @var PushEvent $event */
		$event = $provider->create($request);

		$this->assertInstanceOf('DavidBadura\GitWebhooks\Event\PushEvent', $event);
		$this->assertEquals(null, $event->ref);
		$this->assertEquals('name-of-branch', $event->branchName);
		$this->assertEquals(null, $event->tagName);
		$this->assertEquals('Emma', $event->user->name);
		$this->assertEquals('repo_name', $event->repository->name);
		$this->assertEquals('team_name', $event->repository->namespace);
		$this->assertCount(1, $event->commits);
	}

	public function testTag()
	{
		$this->markTestSkipped('missing example payload');
		return;

		$request = $this->createRequest('push', __DIR__ . '/_files/bitbucket/tag.json');

		$provider = new BitbucketProvider();
		/** @var PushEvent $event */
		$event = $provider->create($request);

		$this->assertInstanceOf('DavidBadura\GitWebhooks\Event\PushEvent', $event);
		$this->assertEquals('refs/tags/test-tag', $event->ref);
		$this->assertEquals(null, $event->branchName);
		$this->assertEquals('test-tag', $event->tagName);
		$this->assertEquals('public-repo', $event->repository->name);
		$this->assertEquals('baxterthehacker', $event->repository->namespace);
		$this->assertCount(1, $event->commits);
		$this->assertEquals('0d1a26e67d8f5eaf1f6ba5c57fc3c7d91ac0fd1c', $event->commits[0]->id);
	}

	public function testPullRequest()
	{
		$request = $this->createRequest('pull_request', __DIR__ . '/_files/bitbucket/pull_request.json');

		$provider = new BitbucketProvider();

		/** @var MergeRequestEvent $event */
		$event = $provider->create($request);

		$this->assertInstanceOf(MergeRequestEvent::class, $event);
		$this->assertEquals(MergeRequestEvent::STATE_OPEN, $event->state);
		$this->assertEquals('Title of pull request', $event->title);
		$this->assertEquals('Description of pull request', $event->description);
		$this->assertEquals('master', $event->targetBranch);
		$this->assertEquals('branch2', $event->sourceBranch);
		$this->assertEquals('repo_name', $event->repository->name);
		$this->assertEquals('team_name', $event->repository->namespace);
		$this->assertEquals('repo_name', $event->sourceRepository->name);
		$this->assertEquals('team_name', $event->sourceRepository->namespace);
		$this->assertEquals('d3022fc0ca3d', $event->lastCommit->id);
	}

	public function testMergedPullRequest()
	{
		$request = $this->createRequest('pull_request', __DIR__ . '/_files/bitbucket/pull_request-merged.json');

		$provider = new BitbucketProvider();

		/** @var MergeRequestEvent $event */
		$event = $provider->create($request);

		$this->assertInstanceOf(MergeRequestEvent::class, $event);
		$this->assertEquals(MergeRequestEvent::STATE_MERGED, $event->state);
		$this->assertEquals('Title of pull request', $event->title);
		$this->assertEquals('Description of pull request', $event->description);
		$this->assertEquals('master', $event->targetBranch);
		$this->assertEquals('branch2', $event->sourceBranch);
		$this->assertEquals('repo_name', $event->repository->name);
		$this->assertEquals('team_name', $event->repository->namespace);
		$this->assertEquals('repo_name', $event->sourceRepository->name);
		$this->assertEquals('team_name', $event->sourceRepository->namespace);
		$this->assertEquals('d3022fc0ca3d', $event->lastCommit->id);
	}

	protected function createRequest(string $event, string $file = null): ServerRequestInterface
	{
		$request = $this->requestFactory->createServerRequest('POST', '', [])
			->withHeader('X-Event-Key', $event);

		if ($file) {
			$request = $request
				->withBody($this->requestFactory->createStreamFromFile($file))
				->withParsedBody(json_decode(file_get_contents($file), true));
		}

		return $request;
	}
}
