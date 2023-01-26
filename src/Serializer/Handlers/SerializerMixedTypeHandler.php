<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\Serializer\Handlers;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;

class SerializerMixedTypeHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return [
            [
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => 'mixed',
                'method' => 'deserializeMixedToJson',
            ],
        ];
    }

    public function deserializeMixedToJson(JsonDeserializationVisitor $visitor, $value, array $type, Context $context)
    {
        return $value;
    }
}