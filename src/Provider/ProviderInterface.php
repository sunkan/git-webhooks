<?php declare(strict_types=1);

namespace DavidBadura\GitWebhooks\Provider;

use DavidBadura\GitWebhooks\Event\AbstractEvent;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @author David Badura <d.a.badura@gmail.com>
 */
interface ProviderInterface
{
	public function create(ServerRequestInterface $request): ?AbstractEvent;

	public function support(ServerRequestInterface $request): bool;
}