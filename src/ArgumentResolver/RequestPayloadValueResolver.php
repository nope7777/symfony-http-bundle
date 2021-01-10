<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\ArgumentResolver;

use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use N7\SymfonyHttpBundle\Exceptions\RequestPayloadValidationFailedException;
use N7\SymfonyHttpBundle\Interfaces\RequestPayloadInterface;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Generator;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Mapping\PropertyMetadataInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Mapping\PropertyMetadata;

final class RequestPayloadValueResolver implements ArgumentValueResolverInterface
{
    private Serializer $serializer;
    private ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->serializer = SerializerBuilder::create()->build();
        $this->validator = $validator;
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
        $payload = $this->cast($argument->getType(), $payload);

        $this->validate($argument->getType(), $payload);

        yield $this->serializer->deserialize(
            json_encode($payload),
            $argument->getType(),
            'json'
        );
    }

    private function cast(string $class, array $payload): array
    {
        $meta = $this->validator->getMetadataFor($class);

        foreach ($meta->properties as $propery) {
            /** @var PropertyMetadata $propery */
            dd(
                $propery->getReflectionMember($propery->class)->getType()->getName(),
                $propery->getReflectionMember($propery->class)->getType()->allowsNull()
            );

            // todo: cast scalar types
            // todo: recursion for nested/nesteds
        }
    }

    private function validate(string $class, array $payload): void
    {
        $constrains = $this->extractConstrainsFromClass($class);

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

    private function extractConstrainsFromClass(string $class): Constraints\Collection
    {
        // todo: move to validatrors-bundle as 'ConstrainsExtractor' service

        // Extracting class metadata
        $meta = $this->validator->getMetadataFor($class);

        // Collecting constraints
        $constraints = array_map(
            fn (PropertyMetadataInterface $property): array => $property->getConstraints(),
            $meta->properties
        );

        return new Constraints\Collection($constraints);
    }
}
