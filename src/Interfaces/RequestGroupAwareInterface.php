<?php

namespace N7\SymfonyHttpBundle\Interfaces;

use Symfony\Component\Validator\Constraint;

interface RequestGroupAwareInterface
{
    /**
     * Returns active groups for a request.
     * Aware that it also must return 'Default' group, to validate non group constaints
     *
     * @see Constraint::DEFAULT_GROUP
     * 
     * @param array $rawPayload
     * @return array
     */
    public static function getGroupSequence(array $rawPayload): array;
}
