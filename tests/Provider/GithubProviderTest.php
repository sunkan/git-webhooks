<?php declare(strict_types=1);

namespace DavidBadura\GitWebhooks\Provider;

use DavidBadura\GitWebhooks\Event\MergeRequestEvent;
use DavidBadura\GitWebhooks\Event\PushEvent;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class GithubProviderTest extends TestCase
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

		$provider = new GithubProvider();

		$this->assertTrue($provider->support($request));
	}

	public function testNoSupport()
	{
		$request = $this->requestFactory->createServerRequest('POST', '', []);

		$provider = new GithubProvider();

		$this->assertFalse($provider->support($request));
	}

	public function testPush()
	{
		$request = $this->createRequest('push', __DIR__ . '/_files/github/push.json');

		$provider = new GithubProvider();
		/** @var PushEvent $event */
		$event = $provider->create($request);

		$this->assertInstanceOf('DavidBadura\GitWebhooks\Event\PushEvent', $event);
		$this->assertEquals('refs/heads/changes', $event->ref);
		$this->assertEquals('changes', $event->branchName);
		$this->assertEquals(null, $event->tagName);
		$this->assertEquals('baxterthehacker', $event->user->name);
		$this->assertEquals('public-repo', $event->repository->name);
		$this->assertEquals('baxterthehacker', $event->repository->namespace);
		$this->assertCount(1, $event->commits);
	}

	public function testTag()
	{
		$request = $this->createRequest('push', __DIR__ . '/_files/github/tag.json');

		$provider = new GithubProvider();
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
		$request = $this->createRequest('pull_request', __DIR__ . '/_files/github/pull_request.json');

		$provider = new GithubProvider();

		/** @var MergeRequestEvent $event */
		$event = $provider->create($request);

		$this->assertInstanceOf('DavidBadura\GitWebhooks\Event\MergeRequestEvent', $event);
		$this->assertEquals('opened', $event->state);
		$this->assertEquals('Update the README with new information', $event->title);
		$this->assertEquals('This is a pretty simple change that we need to pull into master.', $event->description);
		$this->assertEquals('master', $event->targetBranch);
		$this->assertEquals('changes', $event->sourceBranch);
		$this->assertEquals('public-repo', $event->repository->name);
		$this->assertEquals('baxterthehacker', $event->repository->namespace);
		$this->assertEquals('public-repo', $event->sourceRepository->name);
		$this->assertEquals('baxterthehacker', $event->sourceRepository->namespace);
		$this->assertEquals('0d1a26e67d8f5eaf1f6ba5c57fc3c7d91ac0fd1c', $event->lastCommit->id);
	}

	public function testPingRequest()
	{
		$request = $this->createRequest('ping', __DIR__ . '/_files/github/ping.json');

		$provider = new GithubProvider();

		/** @var MergeRequestEvent $event */
		$event = $provider->create($request);

		$this->assertInstanceOf('DavidBadura\GitWebhooks\Event\PingEvent', $event);
		$this->assertEquals('simpspector', $event->repository->name);
		$this->assertEquals('simpspector', $event->repository->namespace);
	}

	protected function createRequest(string $event, string $file = null): ServerRequestInterface
	{
		$request = $this->requestFactory->createServerRequest('POST', '', [])
			->withHeader('X-Github-Event', $event);

		if ($file) {
			$request = $request
				->withBody($this->requestFactory->createStreamFromFile($file))
				->withParsedBody(json_decode(file_get_contents($file), true));
		}

		return $request;
	}
}