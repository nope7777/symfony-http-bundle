# About

This package still under development.

## Requests injection

How it works:
- You create request class, describe it structure with JMS serializer annotations
  and Symfony validation annotations.
- You inject request in your controller action.
- Bundle validates request according to your request class, if request is invalid, it throws
  custom validation exception.
- If request is valid, it is deserialized to your request class.

Important note: HTTP request fields always come as strings, to validate them correctly, they are soft
casted according to type annotations in request class.

You can register listener, that will convert
`N7\SymfonyHttpBundle\EventListener\RequestPayloadExceptionListener` exception to `json` response:

```
# (config/services.yaml)

services:
    # ...
    
    N7\SymfonyHttpBundle\EventListener\RequestPayloadExceptionListener:
        tags:
            - { name: kernel.event_listener, event: kernel.exception }
```

Example:

Request class:

```php
use N7\SymfonyHttpBundle\Interfaces\RequestPayloadInterface;

final class CreateUserRequest implements RequestPayloadInterface
{
    /**
     * @Constraints\NotBlank
     * @Constraints\Type(type="string")
     */
    private string $username;

    /**
     * @Constraints\NotBlank
     * @Constraints\Type(type="integer")
     */
    private int $age;
    
    /**
     * @Constraints\NotBlank
     * @Constraints\Type(type="bool")
     */
    private bool $terms;
}
```

Controller action:

```php
/**
 * @Route("/api/endpoint")
 */
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

## Nested objects and array of objects

Nested objects and arrays of objects are handled with `n7/symfony-validators-bundle` package.

Example:

```php
final class Request implements RequestPayloadInterface
{
    /**
     * @Constraints\NotBlank
     * @NestedObject(NestedObject::class)
     */
    private NestedObject $select2;

    /**
     * @Constraints\NotBlank
     * @NestedObjects(NestedObject::class)
     *
     * @Serializer\Type("array<NestedObject>")
     */
    private array $list;
}
```

## Arrays

One remark about arrays: arrays of integer/float/bool must have `@var` annotation describing their inner
elements scalar type. It needed for soft type casting before validation. Available values: `int[]`,
`float[]`, `boolean[]`, `string[]`.

Example:

```php
final class Request implements RequestPayloadInterface
{
    /**
     * @Constraints\NotBlank
     *
     * @Serializer\Type("array")
     * @var float[]
     */
    private array $amounts;
}
```

## AllowExtraFields and AllowMissingFields

Validatior `allowExtraFields` and `allowMissingFields` parameters can be overwritten with annotations:

```php
use N7\SymfonyValidatorsBundle\Options\AllowExtraFields;
use N7\SymfonyValidatorsBundle\Options\AllowMissingFields;

/**
 * @AllowExtraFields
 * @AllowMissingFields
 */
final class Request implements RequestPayloadInterface
{

}
```

🥔
