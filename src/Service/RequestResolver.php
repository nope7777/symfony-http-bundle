<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\Service;

use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use N7\SymfonyHttpBundle\Exceptions\RequestPayloadValidationFailedException;
use N7\SymfonyHttpBundle\Interfaces\RequestPayloadInterface;
use N7\SymfonyValidatorsBundle\Service\ConstrainsExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Generator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RequestResolver
{
    private const CONTENT_TYPE_JSON = 'json';
    private const METHOD_GET = 'GET';

    private const DESETIALIZATION_FORMAT_JSON = 'json';

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

    public function resolve(Request $request, string $requestClass): RequestPayloadInterface
    {
        $payload = $this->getRequestPayload($request);
        $payload = $this->caster->cast($requestClass, $payload);

        $this->validate($requestClass, $payload);

        return $this->serializer->deserialize(
            json_encode($payload, JSON_THROW_ON_ERROR),
            $requestClass,
            self::DESETIALIZATION_FORMAT_JSON
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
        if ($request->getContentType() === self::CONTENT_TYPE_JSON) {
            return json_decode($request->getContent(), true, JSON_THROW_ON_ERROR);
        }

        if ($request->getMethod() === self::METHOD_GET) {
            return $request->query->all();
        }

        return $request->request->all();
    }
}
