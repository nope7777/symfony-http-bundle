<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\ArgumentResolver;

use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use N7\SymfonyHttpBundle\Exceptions\RequestPayloadValidationFailedException;
use N7\SymfonyHttpBundle\Interfaces\RequestPayloadInterface;
use N7\SymfonyHttpBundle\Service\SoftTypesCaster;
use N7\SymfonyValidatorsBundle\Service\ConstrainsExtractor;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Generator;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Mapping\PropertyMetadataInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Mapping\PropertyMetadata;

// todo: move resolving logic to service
final class RequestPayloadValueResolver implements ArgumentValueResolverInterface
{
    private Serializer $serializer;
    private ValidatorInterface $validator;
    private SoftTypesCaster $caster;
    private ConstrainsExtractor $constrainsExtractor;

    public function __construct(
        ValidatorInterface $validator,
        SoftTypesCaster $caster,
        ConstrainsExtractor $constrainsExtractor
    ) {
        $this->serializer = SerializerBuilder::create()->build();
        $this->validator = $validator;
        $this->caster = $caster;
        $this->constrainsExtractor = $constrainsExtractor;
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
        $payload = $this->caster->cast($argument->getType(), $payload); // todo: types casting is not required for json requests (?)

        $this->validate($argument->getType(), $payload);

        yield $this->serializer->deserialize(
            json_encode($payload),
            $argument->getType(),
            'json'
        );
    }

    private function validate(string $class, array $payload): void
    {
        $constrains = $this->constrainsExtractor->extract($class);

        $result = $this->validator->validate($payload, $constrains);
        if ($result->count()) {
            throw new RequestPayloadValidationFailedException($result, $class, $payload);
        }
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
