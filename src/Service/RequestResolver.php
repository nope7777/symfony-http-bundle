<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\Service;

use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\Handler\HandlerRegistry;
use N7\SymfonyHttpBundle\Exceptions\RequestPayloadValidationFailedException;
use N7\SymfonyHttpBundle\Interfaces\Payload\RequestFormDataInterface;
use N7\SymfonyHttpBundle\Interfaces\Payload\RequestHeaderInterface;
use N7\SymfonyHttpBundle\Interfaces\Payload\RequestJsonPayloadInterface;
use N7\SymfonyHttpBundle\Interfaces\Payload\RequestQueryParametersInterface;
use N7\SymfonyHttpBundle\Interfaces\RequestGroupAwareInterface;
use N7\SymfonyHttpBundle\Interfaces\RequestPayloadInterface;
use N7\SymfonyHttpBundle\Interfaces\RequestPropertyMapInterface;
use N7\SymfonyHttpBundle\Serializer\Handlers\SerializerMixedTypeHandler;
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
    private AnnotationsHandler $annotationsHandler;

    public function __construct(
        ValidatorInterface $validator,
        SoftTypesCaster $caster,
        ConstrainsExtractor $constrainsExtractor,
        AnnotationsHandler $annotationsHandler
    ) {
        $this->validator = $validator;
        $this->caster = $caster;
        $this->constrainsExtractor = $constrainsExtractor;
        $this->annotationsHandler = $annotationsHandler;
        $this->serializer = SerializerBuilder::create()
            ->setPropertyNamingStrategy(new IdenticalPropertyNamingStrategy())
            ->configureHandlers(function(HandlerRegistry $registry) {
                $registry->registerSubscribingHandler(new SerializerMixedTypeHandler());
            })
            ->build();
    }

    public function resolve(
        Request $request,
        string $requestClass,
        ?array $groups = null,
        bool $defaultClassValuesToPayload = true
    ): RequestPayloadInterface {
        $payload = $this->getRequestPayload($request, $requestClass);

        return $this->resolveFromArray($payload, $requestClass, $groups, $defaultClassValuesToPayload);
    }

    public function resolveFromArray(
        array $payload,
        string $requestClass,
        ?array $groups = null,
        bool $defaultClassValuesToPayload = true
    ): RequestPayloadInterface {
        $payload = $this->caster->cast($requestClass, $payload, $defaultClassValuesToPayload);
        $payload = $this->annotationsHandler->apply($requestClass, $payload);

        if (in_array(RequestGroupAwareInterface::class, class_implements($requestClass), true)) {
            $groups = array_merge(
                $requestClass::getGroupSequence($payload),
                $groups ?? []
            );
        }

        $this->validate($requestClass, $payload, $groups);

        return $this->serializer->deserialize(
            json_encode($payload, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_IGNORE),
            $requestClass,
            self::DESETIALIZATION_FORMAT_JSON
        );
    }

    private function validate(string $class, array $payload, ?array $groups = null): void
    {
        $constrains = $this->constrainsExtractor->extract($class);

        if (is_subclass_of($class, RequestPropertyMapInterface::class)) {
            foreach ($class::getPropertyMap() as $property => $map) {
                if (array_key_exists($property, $payload)) {
                    $payload[$map] = $payload[$property];
                    unset($payload[$property]);
                }

                if (array_key_exists($property, $constrains->fields)) {
                    $constrains->fields[$map] = $constrains->fields[$property];
                    unset($constrains->fields[$property]);
                }
            }
        }

        $result = $this->validator->validate($payload, $constrains, $groups);
        if ($result->count()) {
            throw new RequestPayloadValidationFailedException($result, $class, $payload);
        }
    }

    private function getRequestPayload(Request $request, string $class): array
    {
        switch (true) {
            case is_subclass_of($class, RequestQueryParametersInterface::class):
                return $request->query->all();
            case is_subclass_of($class, RequestJsonPayloadInterface::class):
                return json_decode($request->getContent(), true, JSON_THROW_ON_ERROR) ?? [];
            case is_subclass_of($class, RequestFormDataInterface::class):
                return $request->request->all();
            case is_subclass_of($class, RequestHeaderInterface::class):
                return array_map(
                    fn(array $header) => count($header) === 1 ? array_shift($header) : $header,
                    $request->headers->all()
                );
            default:
                return $this->autoDetectRequestPayload($request);
        }
    }

    private function autoDetectRequestPayload(Request $request): array
    {
        // Should be first, only method, that can't contain json payload
        if ($request->getMethod() === self::METHOD_GET) {
            return $request->query->all();
        }

        if ($request->getContentTypeFormat() === self::CONTENT_TYPE_JSON) {
            return json_decode($request->getContent(), true, JSON_THROW_ON_ERROR) ?? [];
        }

        return $request->request->all();
    }
}
