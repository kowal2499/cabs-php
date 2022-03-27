<?php

namespace LegacyFighter\Cabs\Contracts\Model\State\Dynamic\Config\Predicates\StateChange;

use LegacyFighter\Cabs\Contracts\Model\State\Dynamic\ChangeCommand;
use LegacyFighter\Cabs\Contracts\Model\State\Dynamic\State;

class ContentNotEmptyVerifier implements Predicate
{
    public function test(State $state, ChangeCommand $command): bool
    {
        return $state->getDocumentHeader()->getContentId() !== null;
    }
}
