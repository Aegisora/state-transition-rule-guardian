<?php

namespace Aegisora\RuleGuardians\StateTransitionRule;

use Aegisora\Guardian\Exceptions\GuardianExecutingRuleException;
use Aegisora\Guardian\Exceptions\GuardianValidationException;
use Aegisora\Guardian\Guardian;
use Aegisora\Rules\StateTransition\Models\State;
use Aegisora\Rules\StateTransition\Models\StateTransition;
use Aegisora\Rules\StateTransition\Models\StateTransitionMap;
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
     * @param array<array-key, array<string, string[]>> $allowedStateTransitions list of single-entry maps,
     *      each mapping a source state name to its allowed transition state names,
     *      e.g. [['StateA' => ['StateB', 'StateC']], ['StateB' => ['StateD']]]
     * @throws GuardianExecutingRuleException
     * @throws GuardianValidationException
     * @throws Throwable
     */
    public function checkByStateNames(
        string $fromStateName,
        string $toStateName,
        array $allowedStateTransitions,
        ?Throwable $exception = null
    ): void {
        $this->guardian->check(
            StateTransition::create(State::create($fromStateName), State::create($toStateName)),
            StateTransitionRule::create(StateTransitionMaps::createFromArray($allowedStateTransitions)),
            $exception
        );
    }

    /**
     * @param StateTransitionMap[] $allowedStateTransitions
     * @throws GuardianExecutingRuleException
     * @throws GuardianValidationException
     * @throws Throwable
     */
    public function checkByStates(
        State $from,
        State $to,
        array $allowedStateTransitions,
        ?Throwable $exception = null
    ): void {
        $this->guardian->check(
            StateTransition::create($from, $to),
            StateTransitionRule::create(StateTransitionMaps::create($allowedStateTransitions)),
            $exception
        );
    }

    /**
     * @throws GuardianExecutingRuleException
     * @throws GuardianValidationException
     * @throws Throwable
     */
    public function checkTransition(
        StateTransition $checkingStateTransition,
        StateTransitionMaps $allowedStateTransitions,
        ?Throwable $exception = null
    ): void {
        $this->guardian->check(
            $checkingStateTransition,
            StateTransitionRule::create($allowedStateTransitions),
            $exception
        );
    }
}
