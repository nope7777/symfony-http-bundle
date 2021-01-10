<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\ArgumentResolver;

use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use N7\SymfonyHttpBundle\Interfaces\RequestPayloadInterface;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Generator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RequestPayloadValueResolver implements ArgumentValueResolverInterface
{
    private Serializer $serializer;

    public function __construct(ValidatorInterface $validator)
    {
        $this->serializer = SerializerBuilder::create()->build();
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        $type = $argument->getType();
        if (! $type || ! class_exists($type)) {
            return false;
        }

        $interfaces = class_implements($type);

        return array_key_exists(RequestPayloadInterface::class, $interfaces);
    }

    public function resolve(Request $request, ArgumentMetadata $argument): Generator
    {
        $payload = $this->getRequestPayload($request);

        // todo: types casting
        // todo: validaton

        yield $this->serializer->deserialize(
            json_encode($payload),
            $argument->getType(),
            'json'
        );
    }

    private function getRequestPayload(Request $request): array
    {
        if ($request->getContentType() === 'json') { // todo: move to constant
            return json_decode($request->getContent(), true, JSON_THROW_ON_ERROR);
        }

        if ($request->getMethod() === 'GET') { // todo: move to constant
            return $request->query->all();
        }

        return $request->request->all();
    }
}
