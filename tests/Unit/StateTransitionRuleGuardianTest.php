<?php

namespace Aegisora\RuleGuardians\StateTransitionRule\Tests\Unit;

use Aegisora\Guardian\Exceptions\GuardianExecutingRuleException;
use Aegisora\Guardian\Exceptions\GuardianValidationException;
use Aegisora\Guardian\Guardian;
use Aegisora\RuleGuardians\StateTransitionRule\StateTransitionRuleGuardian;
use Aegisora\Rules\StateTransition\Models\State;
use Aegisora\Rules\StateTransition\Models\StateTransition;
use Aegisora\Rules\StateTransition\Models\StateTransitionMap;
use Aegisora\Rules\StateTransition\Models\StateTransitionMaps;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Throwable;

class StateTransitionRuleGuardianTest extends TestCase
{
    private const RULE_CODE = 'state_transition_rule';

    private StateTransitionRuleGuardian $guardian;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guardian = new StateTransitionRuleGuardian(
            new Guardian()
        );
    }

    /**
     * @dataProvider getAllowedTransitionProvidedData
     * @param array<array-key, array<string, string[]>> $allowedStateTransitions
     */
    public function testCheckByStateNamesSuccessfully(
        string $fromStateName,
        string $toStateName,
        array $allowedStateTransitions
    ): void {
        $this->expectNotToPerformAssertions();

        $this->guardian->checkByStateNames($fromStateName, $toStateName, $allowedStateTransitions);
    }

    /**
     * @dataProvider getAllowedTransitionProvidedData
     * @param array<array-key, array<string, string[]>> $allowedStateTransitions
     */
    public function testCheckByStatesSuccessfully(
        string $fromStateName,
        string $toStateName,
        array $allowedStateTransitions
    ): void {
        $this->expectNotToPerformAssertions();

        $this->guardian->checkByStates(
            State::create($fromStateName),
            State::create($toStateName),
            self::toStateTransitionMapArray($allowedStateTransitions)
        );
    }

    /**
     * @dataProvider getAllowedTransitionProvidedData
     * @param array<array-key, array<string, string[]>> $allowedStateTransitions
     */
    public function testCheckTransitionSuccessfully(
        string $fromStateName,
        string $toStateName,
        array $allowedStateTransitions
    ): void {
        $this->expectNotToPerformAssertions();

        $this->guardian->checkTransition(
            StateTransition::create(State::create($fromStateName), State::create($toStateName)),
            StateTransitionMaps::create(self::toStateTransitionMapArray($allowedStateTransitions))
        );
    }

    public static function getAllowedTransitionProvidedData(): array
    {
        return [
            'source state exists, target state exists' => [
                'fromStateName' => 'StateA',
                'toStateName' => 'StateB',
                'allowedStateTransitions' => [
                    ['StateB' => []],
                    ['StateC' => []],
                    ['StateA' => ['StateB']],
                    ['StateD' => []],
                ],
            ],
            'target state exists after non matching states' => [
                'fromStateName' => 'StateA',
                'toStateName' => 'StateD',
                'allowedStateTransitions' => [
                    ['StateA' => ['StateB', 'StateC', 'StateD']],
                ],
            ],
            'first source state map wins when first is valid' => [
                'fromStateName' => 'StateA',
                'toStateName' => 'StateB',
                'allowedStateTransitions' => [
                    ['StateA' => ['StateB']],
                    ['StateA' => ['StateC']],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getNotAllowedTransitionProvidedData
     * @param array<array-key, array<string, string[]>> $allowedStateTransitions
     */
    public function testCheckByStateNamesFailedWithDefaultCustomException(
        string $fromStateName,
        string $toStateName,
        array $allowedStateTransitions
    ): void {
        $this->expectException(GuardianValidationException::class);

        try {
            $this->guardian->checkByStateNames($fromStateName, $toStateName, $allowedStateTransitions);
        } catch (GuardianValidationException $exception) {
            self::assertSame(self::RULE_CODE, $exception->getRuleCode());

            throw $exception;
        }
    }

    /**
     * @dataProvider getNotAllowedTransitionProvidedData
     * @param array<array-key, array<string, string[]>> $allowedStateTransitions
     */
    public function testCheckByStatesFailedWithDefaultCustomException(
        string $fromStateName,
        string $toStateName,
        array $allowedStateTransitions
    ): void {
        $this->expectException(GuardianValidationException::class);

        try {
            $this->guardian->checkByStates(
                State::create($fromStateName),
                State::create($toStateName),
                self::toStateTransitionMapArray($allowedStateTransitions)
            );
        } catch (GuardianValidationException $exception) {
            self::assertSame(self::RULE_CODE, $exception->getRuleCode());

            throw $exception;
        }
    }

    /**
     * @dataProvider getNotAllowedTransitionProvidedData
     * @param array<array-key, array<string, string[]>> $allowedStateTransitions
     */
    public function testCheckTransitionFailedWithDefaultCustomException(
        string $fromStateName,
        string $toStateName,
        array $allowedStateTransitions
    ): void {
        $this->expectException(GuardianValidationException::class);

        try {
            $this->guardian->checkTransition(
                StateTransition::create(State::create($fromStateName), State::create($toStateName)),
                StateTransitionMaps::create(self::toStateTransitionMapArray($allowedStateTransitions))
            );
        } catch (GuardianValidationException $exception) {
            self::assertSame(self::RULE_CODE, $exception->getRuleCode());

            throw $exception;
        }
    }

    public static function getNotAllowedTransitionProvidedData(): array
    {
        return [
            'allowed transition maps - empty' => [
                'fromStateName' => 'StateA',
                'toStateName' => 'StateB',
                'allowedStateTransitions' => [],
            ],
            'source state not exists' => [
                'fromStateName' => 'StateA',
                'toStateName' => 'StateB',
                'allowedStateTransitions' => [
                    ['StateB' => []],
                    ['StateC' => []],
                    ['StateD' => []],
                ],
            ],
            'source state exists, allowed transition states - empty' => [
                'fromStateName' => 'StateA',
                'toStateName' => 'StateB',
                'allowedStateTransitions' => [
                    ['StateB' => []],
                    ['StateC' => []],
                    ['StateA' => []],
                    ['StateD' => []],
                ],
            ],
            'source state exists, target state not exists' => [
                'fromStateName' => 'StateA',
                'toStateName' => 'StateB',
                'allowedStateTransitions' => [
                    ['StateB' => []],
                    ['StateC' => []],
                    ['StateA' => ['StateD']],
                    ['StateD' => []],
                ],
            ],
            'first source state map wins when first is invalid' => [
                'fromStateName' => 'StateA',
                'toStateName' => 'StateB',
                'allowedStateTransitions' => [
                    ['StateA' => ['StateC']],
                    ['StateA' => ['StateB']],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getFailedCheckProvidedData
     */
    public function testCheckByStateNamesFailed(
        ?Throwable $customRuleValidationException,
        string $expectedExceptionClassName
    ): void {
        $this->expectException($expectedExceptionClassName);

        $this->guardian->checkByStateNames('StateA', 'StateB', [], $customRuleValidationException);
    }

    /**
     * @dataProvider getFailedCheckProvidedData
     */
    public function testCheckByStatesFailed(
        ?Throwable $customRuleValidationException,
        string $expectedExceptionClassName
    ): void {
        $this->expectException($expectedExceptionClassName);

        $this->guardian->checkByStates(
            State::create('StateA'),
            State::create('StateB'),
            [],
            $customRuleValidationException
        );
    }

    /**
     * @dataProvider getFailedCheckProvidedData
     */
    public function testCheckTransitionFailed(
        ?Throwable $customRuleValidationException,
        string $expectedExceptionClassName
    ): void {
        $this->expectException($expectedExceptionClassName);

        $this->guardian->checkTransition(
            StateTransition::create(State::create('StateA'), State::create('StateB')),
            StateTransitionMaps::create([]),
            $customRuleValidationException
        );
    }

    public static function getFailedCheckProvidedData(): array
    {
        return [
            'custom exception - null' => [
                'customRuleValidationException' => null,
                'expectedExceptionClassName' => GuardianValidationException::class,
            ],
            'custom exception - not null' => [
                'customRuleValidationException' => new CustomRuleException(),
                'expectedExceptionClassName' => CustomRuleException::class,
            ],
        ];
    }

    public function testFailedCheckCauseGuardianThrowsGuardianExecutingRuleException(): void
    {
        $this->expectException(GuardianExecutingRuleException::class);

        $guardian = new StateTransitionRuleGuardian(
            $this->getGuardianThrowsExceptionOnCheck(GuardianExecutingRuleException::class)
        );

        $guardian->checkTransition(
            StateTransition::create(State::create('StateA'), State::create('StateB')),
            StateTransitionMaps::create([])
        );
    }

    public function testFailedCheckCauseGuardianThrowsNotExpectedException(): void
    {
        $this->expectException(Throwable::class);

        $guardian = new StateTransitionRuleGuardian(
            $this->getGuardianThrowsExceptionOnCheck(Throwable::class)
        );

        $guardian->checkTransition(
            StateTransition::create(State::create('StateA'), State::create('StateB')),
            StateTransitionMaps::create([])
        );
    }

    /**
     * @return Guardian|MockObject
     */
    private function getGuardianThrowsExceptionOnCheck(string $expectedExceptionClass): Guardian
    {
        $guardian = $this->getGuardianMock();

        $guardian
            ->expects(self::once())
            ->method('check')
            ->willThrowException($this->createMock($expectedExceptionClass));

        return $guardian;
    }

    /**
     * @return Guardian|MockObject
     */
    private function getGuardianMock(): Guardian
    {
        /** @var Guardian|MockObject $mock */
        $mock = $this->createMock(Guardian::class);

        return $mock;
    }

    /**
     * @param array<array-key, array<string, string[]>> $allowedStateTransitions
     * @return StateTransitionMap[]
     */
    private static function toStateTransitionMapArray(array $allowedStateTransitions): array
    {
        $stateTransitionMaps = [];

        foreach ($allowedStateTransitions as $map) {
            foreach ($map as $sourceStateName => $transitionStateNames) {
                $stateTransitionMaps[] = StateTransitionMap::create(
                    State::create($sourceStateName),
                    array_map(
                        static function (string $transitionStateName): State {
                            return State::create($transitionStateName);
                        },
                        $transitionStateNames
                    )
                );
            }
        }

        return $stateTransitionMaps;
    }
}
