<?php

namespace LegacyFighter\Cabs\Contracts\Model\State\Dynamic\Config\Predicates\ContentChange;

use LegacyFighter\Cabs\Contracts\Model\State\Dynamic\State;

class PositivePredicate implements Predicate
{
    public function test(State $state): bool
    {
        return true;
    }

}
