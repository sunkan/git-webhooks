<?php declare(strict_types=1);

namespace DavidBadura\GitWebhooks\Provider;

use DavidBadura\GitWebhooks\Event\MergeRequestEvent;
use DavidBadura\GitWebhooks\Event\PushEvent;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class GitlabProviderTest extends TestCase
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

		$provider = new GitlabProvider();

		$this->assertTrue($provider->support($request));
	}

	public function testNoSupport()
	{
		$request = $this->requestFactory->createServerRequest('POST', '', []);

		$provider = new GitlabProvider();

		$this->assertFalse($provider->support($request));
	}

	public function testPush()
	{
		$request = $this->createRequest('Push Hook', __DIR__ . '/_files/gitlab/push.json');

		$provider = new GitlabProvider();

		/** @var PushEvent $event */
		$event = $provider->create($request);

		$this->assertInstanceOf('DavidBadura\GitWebhooks\Event\PushEvent', $event);
		$this->assertEquals('refs/heads/master', $event->ref);
		$this->assertEquals('master', $event->branchName);
		$this->assertEquals(null, $event->tagName);
		$this->assertEquals('John Smith', $event->user->name);
		$this->assertEquals('Diaspora', $event->repository->name);
		$this->assertEquals('Mike', $event->repository->namespace);
		$this->assertCount(2, $event->commits);
	}

	public function testTag()
	{
		$request = $this->createRequest('Tag Push Hook', __DIR__ . '/_files/gitlab/tag.json');

		$provider = new GitlabProvider();

		/** @var PushEvent $event */
		$event = $provider->create($request);

		$this->assertInstanceOf('DavidBadura\GitWebhooks\Event\PushEvent', $event);
		$this->assertEquals('refs/tags/v1.0.0', $event->ref);
		$this->assertEquals(null, $event->branchName);
		$this->assertEquals('v1.0.0', $event->tagName);
		$this->assertEquals('Example', $event->repository->name);
		$this->assertEquals('Jsmith', $event->repository->namespace);
	}

	public function testMergeRequest()
	{
		$request = $this->createRequest('Merge Request Hook', __DIR__ . '/_files/gitlab/merge_request.json');

		$provider = new GitlabProvider();

		/** @var MergeRequestEvent $event */
		$event = $provider->create($request);

		$this->assertInstanceOf('DavidBadura\GitWebhooks\Event\MergeRequestEvent', $event);
		$this->assertEquals('opened', $event->state);
		$this->assertEquals('MS-Viewport', $event->title);
		$this->assertEquals('', $event->description);
		$this->assertEquals('master', $event->targetBranch);
		$this->assertEquals('ms-viewport', $event->sourceBranch);
		$this->assertEquals('Awesome Project', $event->repository->name);
		$this->assertEquals('Awesome Space', $event->repository->namespace);
		$this->assertEquals('Awesome Project', $event->sourceRepository->name);
		$this->assertEquals('Awesome Space', $event->sourceRepository->namespace);
		$this->assertEquals('da1560886d4f094c3e6c9ef40349f7d38b5d27d7', $event->lastCommit->id);
	}

	protected function createRequest(string $event, string $file = null): ServerRequestInterface
	{
		$request = $this->requestFactory->createServerRequest('POST', '', [])
			->withHeader('X-Gitlab-Event', $event);

		if ($file) {
			$request = $request
				->withBody($this->requestFactory->createStreamFromFile($file))
				->withParsedBody(json_decode(file_get_contents($file), true));
		}

		return $request;
	}
}