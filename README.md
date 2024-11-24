# About

This bundle allows you to describe requests as classes and inject them into the controller. If a request fails validation, an exception with validation errors will be thrown, which can be handled and the response returned in the required format. If the request is valid, you will have an object with a well-described structure in the controller, all that remains is to process the request.

## Handling validation errors

Bundle comes with a listener that converts validation errors into a response, you can use it as an example to handle errors in the format you need. To use it, add the following lines to your `config/services.yaml`:

```
# (config/services.yaml)

services:
    # ...
    
    N7\SymfonyHttpBundle\EventListener\RequestPayloadExceptionListener:
        tags:
            - { name: kernel.event_listener, event: kernel.exception }
```

This listener will return validation errors in the following json format (example):

```json
{
  "code": 422,
  "message": "Validation error occurred",
  "errors": {
    "[username]": "This field is missing.",
    "[age]": "This field is missing.",
    "[terms]": "This field is missing."
  }
}
```

## Requests injection

*HTTP requests query parameters always come as strings, to validate them correctly, they are soft
casted according to type annotations in request class.*

Request class:

```php
use N7\SymfonyHttpBundle\Interfaces\RequestPayloadInterface;

final class CreateUserRequest implements RequestPayloadInterface
{
    #[Constraints\NotBlank]
    #[Constraints\Type('string')]
    private string $username;

    #[Constraints\NotBlank]
    #[Constraints\Type('integer')]
    private int $age;
    
    #[Constraints\NotBlank]
    #[Constraints\Type('boolean')]
    private bool $terms;
}
```

Controller action:

```php
#[Route('/api/endpoint')]
public function create(CreateUserRequest $request): Response
{
    dd($request);
}
```

Response for request without parameters:

```json
{
  "code": 422,
  "message": "Validation error occurred",
  "errors": {
    "[username]": "This field is missing.",
    "[age]": "This field is missing.",
    "[terms]": "This field is missing."
  }
}
```

If request class implements the `RequestPayloadInterface`, the request type will be determined automatically (get/form-data/json). If you want to specify exactly what type of request to use, then you can implement one of these interfaces:
- `RequestQueryParametersInterface` - query parameters (GET request)
- `RequestFormDataInterface` - form data (POST/PUT/... requests)
- `RequestJsonPayloadInterface` - json (POST/PUT/... requests)

## Mutators

In case you need to transform the field value before validation, you can make a custom mutator (example):

```php
use N7\SymfonyHttpBundle\Annotations\ValueMutatorInterface;

#[\Attribute]
class StringLimitMutator implements ValueMutatorInterface
{
    public function mutate($value, array $payload, string $property)
    {
        if (! is_string($value)) {
            return $value;
        }
    
        return mb_substr($value, 0, 50);
    }
}

final class TestRequest implements RequestPayloadInterface
{
    #[Constraints\NotBlank]
    #[Constraints\Type('string')]
    #[StringLimitMutator]
    private string $address;
}
```

## Nested objects and array of objects

The example below shows how to work with nested objects and arrays of nested objects:

```php
use JMS\Serializer\Annotation as Serializer;

final class Request implements RequestPayloadInterface
{
    #[Constraints\NotBlank]
    #[NestedObject(NestedObject::class)]
    private NestedObject $select2;

    #[Constraints\NotBlank]
    #[NestedObjects(NestedObject::class)]
    #[Serializer\Type("array<...\NestedObject>")]
    private array $list;
}
```

## Arrays

arrays of integer/float/bool must have `@var` annotation describing their inner
elements scalar type. It needed for soft type casting before validation. Available values: `int[]`,
`float[]`, `boolean[]`, `string[]`.

Example:

```php
final class Request implements RequestPayloadInterface
{
    /**
     * @var float[]
     */
    #[Constraints\NotBlank]
    #[Serializer\Type("array")]
    private array $amounts;
}
```

## AllowExtraFields and AllowMissingFields

Validatior `allowExtraFields` and `allowMissingFields` parameters can be overwritten with annotations:

```php
use N7\SymfonyValidatorsBundle\Options\AllowExtraFields;
use N7\SymfonyValidatorsBundle\Options\AllowMissingFields;

#[AllowExtraFields]
#[AllowMissingFields]
final class Request implements RequestPayloadInterface
{

}
```
