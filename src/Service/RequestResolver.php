<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\Service;

use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use N7\SymfonyHttpBundle\Exceptions\RequestPayloadValidationFailedException;
use N7\SymfonyHttpBundle\Interfaces\RequestGroupAwareInterface;
use N7\SymfonyHttpBundle\Interfaces\RequestPayloadInterface;
use N7\SymfonyValidatorsBundle\Service\ConstrainsExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
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
        $this->validator = $validator;
        $this->caster = $caster;
        $this->constrainsExtractor = $constrainsExtractor;
        $this->serializer = SerializerBuilder::create()
            ->setPropertyNamingStrategy(new IdenticalPropertyNamingStrategy())
            ->build();
    }

    public function resolve(Request $request, string $requestClass, ?array $groups = null): RequestPayloadInterface
    {
        $payload = $this->getRequestPayload($request);
        $payload = $this->caster->cast($requestClass, $payload);

        if (in_array(RequestGroupAwareInterface::class, class_implements($requestClass), true)) {
            $groups = array_merge(
                $requestClass::getGroupSequence($payload),
                $groups ?? []
            );
        }

        $this->validate($requestClass, $payload, $groups);

        return $this->serializer->deserialize(
            json_encode($payload, JSON_THROW_ON_ERROR),
            $requestClass,
            self::DESETIALIZATION_FORMAT_JSON
        );
    }

    private function validate(string $class, array $payload, ?array $groups = null): void
    {
        $constrains = $this->constrainsExtractor->extract($class);

        $result = $this->validator->validate($payload, $constrains, $groups);
        if ($result->count()) {
            throw new RequestPayloadValidationFailedException($result, $class, $payload);
        }
    }

    private function getRequestPayload(Request $request): array
    {
        // Should be first, only method, that can't contain json payload
        if ($request->getMethod() === self::METHOD_GET) {
            return $request->query->all();
        }
        
        if ($request->getContentType() === self::CONTENT_TYPE_JSON) {
            return json_decode($request->getContent(), true, JSON_THROW_ON_ERROR) ?? [];
        }

        return $request->request->all();
    }
}
