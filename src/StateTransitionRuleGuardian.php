<?php

namespace Aegisora\RuleGuardians\StateTransitionRule;

use Aegisora\Guardian\Exceptions\GuardianExecutingRuleException;
use Aegisora\Guardian\Exceptions\GuardianValidationException;
use Aegisora\Guardian\Guardian;
use Aegisora\Rules\StateTransition\Models\StateTransition;
use Aegisora\Rules\StateTransition\Models\StateTransitionMaps;
use Aegisora\Rules\StateTransition\StateTransitionRule;
use Throwable;

class StateTransitionRuleGuardian
{
    private Guardian $guardian;

    public function __construct(
        Guardian $guardian
    ) {
        $this->guardian = $guardian;
    }

    /**
     * @throws GuardianExecutingRuleException
     * @throws GuardianValidationException
     * @throws Throwable
     */
    public function check(
        StateTransition $checkingStateTransition,
        StateTransitionMaps $allowedStateTransitions,
        ?Throwable $exception = null
    ): void {
        $this->guardian->check($checkingStateTransition, StateTransitionRule::create($allowedStateTransitions), $exception);
    }
}
