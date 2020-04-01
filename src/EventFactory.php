<?php declare(strict_types=1);

namespace DavidBadura\GitWebhooks;

use DavidBadura\GitWebhooks\Event\AbstractEvent;
use DavidBadura\GitWebhooks\Provider\GithubProvider;
use DavidBadura\GitWebhooks\Provider\GitlabProvider;
use DavidBadura\GitWebhooks\Provider\ProviderInterface;
use Psr\Http\Message\ServerRequestInterface;

class EventFactory
{
	/** @var ProviderInterface[] */
	protected $providers = [];

	/**
	 * @param ProviderInterface[] $providers
	 */
	public function __construct(array $providers = [])
	{
		foreach ($providers as $provider) {
			$this->addProvider($provider);
		}
	}

	public function create(ServerRequestInterface $request): ?AbstractEvent
	{
		foreach ($this->providers as $provider) {
			if (!$provider->support($request)) {
				continue;
			}

			return $provider->create($request);
		}

		return null;
	}

	public function addProvider(ProviderInterface $provider): self
	{
		$this->providers[] = $provider;

		return $this;
	}

	public static function createDefault(): self
	{
		return new self([
			new GitlabProvider(),
			new GithubProvider()
		]);
	}
}
