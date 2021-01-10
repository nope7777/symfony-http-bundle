<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\ArgumentResolver;

use N7\SymfonyHttpBundle\Interfaces\RequestPayloadInterface;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final class RequestPayloadValueResolver implements ArgumentValueResolverInterface
{
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        $type = $argument->getType();
        if (! $type || ! class_exists($type)) {
            return false;
        }

        $interfaces = class_implements($type);

        return array_key_exists(RequestPayloadInterface::class, $interfaces);
    }

    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        throw new \RuntimeException('it works');
        yield $this->security->getUser();
    }
}
