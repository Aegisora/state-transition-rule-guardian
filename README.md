# Aegisora State Transition Rule Guardian

[![Latest Version](https://img.shields.io/packagist/v/aegisora/state-transition-rule-guardian?style=flat-square)](https://packagist.org/packages/aegisora/state-transition-rule-guardian)
[![Total Downloads](https://img.shields.io/packagist/dt/aegisora/state-transition-rule-guardian?style=flat-square)](https://packagist.org/packages/aegisora/state-transition-rule-guardian)
![Code Coverage Badge](./badge.svg)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
![PHPStan Badge](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg?style=flat)

State Transition Rule Guardian provides a simple shortcut for state transition validation using `aegisora/guardian` and `aegisora/state-transition-rule`.

It is designed for cases where you want to quickly check whether a transition **from one state to another is allowed**, without manually building a `StateTransitionRule` and a validation pipeline by hand.

This package is built on top of:

* [aegisora/guardian](https://github.com/Aegisora/guardian)
* [aegisora/state-transition-rule](https://github.com/Aegisora/state-transition-rule)

---

## ✨ Features

* 🔹 Simple shortcut API for `StateTransitionRule`
* 🔹 Validates a transition against a set of allowed transition maps
* 🔹 Three entry points: by state names, by `State` objects, or by domain models
* 🔹 Uses `aegisora/guardian` internally
* 🔹 Uses `aegisora/state-transition-rule` internally
* 🔹 Supports a custom validation exception
* 🔹 Keeps rule execution errors separated from validation errors
* 🔹 Fully compatible with the Aegisora ecosystem
* 🔹 Ready to use out of the box

---

## 📦 Installation

```bash
composer require aegisora/state-transition-rule-guardian
```

---

## 🚀 Core Concept

This package wraps the common state transition validation flow:

```php
$guardian->check(
    StateTransition::create($from, $to),
    StateTransitionRule::create($allowedStateTransitions),
    new TransitionNotAllowedException()
);
```

into a dedicated shortcut class:

```php
$stateTransitionRuleGuardian->checkByStateNames(
    'StateA',
    'StateB',
    [['StateA' => ['StateB']]],
    new TransitionNotAllowedException()
);
```

Instead of manually creating a `StateTransitionRule` and passing it to `Guardian`, you can use `StateTransitionRuleGuardian` directly.

---

## 🏗️ Basic Usage

```php
use Aegisora\Guardian\Guardian;
use Aegisora\Guardian\Exceptions\GuardianValidationException;
use Aegisora\RuleGuardians\StateTransitionRule\StateTransitionRuleGuardian;

$guardian = new Guardian();

$stateTransitionRuleGuardian = new StateTransitionRuleGuardian($guardian);

try {
    $stateTransitionRuleGuardian->checkByStateNames(
        'StateA',
        'StateB',
        [['StateA' => ['StateB', 'StateC']]]
    );
    // transition StateA -> StateB is allowed
} catch (GuardianValidationException $exception) {
    // transition StateA -> StateB is not allowed
}
```

A check **passes when** the source state exists in the allowed transition maps and the target state is listed among its allowed transitions, and **fails otherwise**.

---

## ✅ How the transition check works

A transition is considered valid when the source state is present in the allowed transition maps **and** the target state is among its allowed transition states:

```php
// StateA -> StateB is listed => passes
$stateTransitionRuleGuardian->checkByStateNames('StateA', 'StateB', [
    ['StateA' => ['StateB', 'StateC']],
]);

// allowed transition maps are empty => fails
$stateTransitionRuleGuardian->checkByStateNames('StateA', 'StateB', []);

// source state StateA is not present => fails
$stateTransitionRuleGuardian->checkByStateNames('StateA', 'StateB', [
    ['StateB' => []],
    ['StateC' => []],
]);

// source state StateA has no allowed transitions => fails
$stateTransitionRuleGuardian->checkByStateNames('StateA', 'StateB', [
    ['StateA' => []],
]);

// target state StateB is not among StateA transitions => fails
$stateTransitionRuleGuardian->checkByStateNames('StateA', 'StateB', [
    ['StateA' => ['StateD']],
]);
```

When several maps share the same source state, the **first** matching map wins.

---

## 🧩 Choosing an entry point

The guardian exposes three methods for the same check, differing only by how you supply the transition and the allowed maps.

### `checkByStateNames()` — plain strings

The allowed transitions are a **list of single-entry maps**, each mapping a source state name to its allowed transition state names:

```php
$stateTransitionRuleGuardian->checkByStateNames(
    'StateA',
    'StateB',
    [
        ['StateA' => ['StateB', 'StateC']],
        ['StateB' => ['StateD']],
    ]
);
```

### `checkByStates()` — `State` objects and `StateTransitionMap[]`

```php
use Aegisora\Rules\StateTransition\Models\State;
use Aegisora\Rules\StateTransition\Models\StateTransitionMap;

$stateTransitionRuleGuardian->checkByStates(
    State::create('StateA'),
    State::create('StateB'),
    [
        StateTransitionMap::create(State::create('StateA'), [State::create('StateB'), State::create('StateC')]),
        StateTransitionMap::create(State::create('StateB'), [State::create('StateD')]),
    ]
);
```

### `checkTransition()` — domain models

```php
use Aegisora\Rules\StateTransition\Models\State;
use Aegisora\Rules\StateTransition\Models\StateTransition;
use Aegisora\Rules\StateTransition\Models\StateTransitionMap;
use Aegisora\Rules\StateTransition\Models\StateTransitionMaps;

$stateTransitionRuleGuardian->checkTransition(
    StateTransition::create(State::create('StateA'), State::create('StateB')),
    StateTransitionMaps::create([
        StateTransitionMap::create(State::create('StateA'), [State::create('StateB')]),
    ])
);
```

---

## 🧩 Usage with Custom Exception

You may provide your own exception for validation failure. It must be the **last** argument of any method.

```php
use Aegisora\Guardian\Guardian;
use Aegisora\RuleGuardians\StateTransitionRule\StateTransitionRuleGuardian;
use App\Exceptions\TransitionNotAllowedException;

$guardian = new Guardian();

$stateTransitionRuleGuardian = new StateTransitionRuleGuardian($guardian);

$stateTransitionRuleGuardian->checkByStateNames(
    'StateA',
    'StateB',
    [['StateA' => ['StateC']]],
    new TransitionNotAllowedException()
);
```

If the transition is not allowed, the provided exception will be thrown instead of `GuardianValidationException`.

This is useful when validation errors should have domain-specific meaning.

---

## 🧪 Example in Application Service

```php
use Aegisora\RuleGuardians\StateTransitionRule\StateTransitionRuleGuardian;
use App\Exceptions\OrderTransitionNotAllowedException;

final class OrderStatusChanger
{
    private const ALLOWED_TRANSITIONS = [
        ['new' => ['paid', 'canceled']],
        ['paid' => ['shipped', 'refunded']],
        ['shipped' => ['delivered']],
    ];

    private StateTransitionRuleGuardian $stateTransitionRuleGuardian;

    public function __construct(
        StateTransitionRuleGuardian $stateTransitionRuleGuardian
    ) {
        $this->stateTransitionRuleGuardian = $stateTransitionRuleGuardian;
    }

    public function change(string $currentStatus, string $newStatus): void
    {
        $this->stateTransitionRuleGuardian->checkByStateNames(
            $currentStatus,
            $newStatus,
            self::ALLOWED_TRANSITIONS,
            new OrderTransitionNotAllowedException()
        );

        // business logic for applying the new status
    }
}
```

---

## 🚨 Exceptions

The package raises validation-related exceptions, all delegated to `Guardian` (the outcome of running the rule):

### `GuardianValidationException`

Thrown when validation fails and no custom exception is provided.

The rule code for a failed transition check is `state_transition_rule`.

```php
use Aegisora\Guardian\Exceptions\GuardianValidationException;

try {
    $stateTransitionRuleGuardian->checkByStateNames('StateA', 'StateB', []);
} catch (GuardianValidationException $exception) {
    echo $exception->getRuleCode(); // "state_transition_rule"
}
```

### Custom exception

When a custom exception is passed as the last argument, it is thrown instead of `GuardianValidationException` on validation failure.

```php
use App\Exceptions\TransitionNotAllowedException;

try {
    $stateTransitionRuleGuardian->checkByStateNames(
        'StateA',
        'StateB',
        [],
        new TransitionNotAllowedException()
    );
} catch (TransitionNotAllowedException $exception) {
    // domain-specific handling
}
```

### `GuardianExecutingRuleException`

Thrown when the underlying rule fails to execute (raises a `RuleException` during validation), as opposed to simply reporting an invalid result.

The transition check works on typed transition models and reports disallowed transitions as an invalid result, so this exception is not triggered by the input itself — it is surfaced only if `Guardian` fails to execute the rule.

```php
use Aegisora\Guardian\Exceptions\GuardianExecutingRuleException;

try {
    $stateTransitionRuleGuardian->checkByStateNames('StateA', 'StateB', []);
} catch (GuardianExecutingRuleException $exception) {
    // the rule could not be executed
}
```

---

## 🧩 API

### `StateTransitionRuleGuardian::checkByStateNames()`

```php
/**
 * @param array<array-key, array<string, string[]>> $allowedStateTransitions
 * @throws GuardianExecutingRuleException
 * @throws GuardianValidationException
 * @throws \Throwable
 */
public function checkByStateNames(
    string $fromStateName,
    string $toStateName,
    array $allowedStateTransitions,
    ?\Throwable $exception = null
): void
```

Validates a transition described by plain state names.

Arguments:

* `$fromStateName` — the source state name
* `$toStateName` — the target state name
* `$allowedStateTransitions` — a list of single-entry maps, each mapping a source state name to its allowed transition state names, e.g. `[['StateA' => ['StateB', 'StateC']], ['StateB' => ['StateD']]]`
* `$exception` — an optional custom `\Throwable` to be thrown on validation failure

### `StateTransitionRuleGuardian::checkByStates()`

```php
/**
 * @param StateTransitionMap[] $allowedStateTransitions
 * @throws GuardianExecutingRuleException
 * @throws GuardianValidationException
 * @throws \Throwable
 */
public function checkByStates(
    State $from,
    State $to,
    array $allowedStateTransitions,
    ?\Throwable $exception = null
): void
```

Validates a transition described by `State` objects and an array of `StateTransitionMap`.

### `StateTransitionRuleGuardian::checkTransition()`

```php
/**
 * @throws GuardianExecutingRuleException
 * @throws GuardianValidationException
 * @throws \Throwable
 */
public function checkTransition(
    StateTransition $checkingStateTransition,
    StateTransitionMaps $allowedStateTransitions,
    ?\Throwable $exception = null
): void
```

Validates a transition described by the `StateTransition` and `StateTransitionMaps` domain models.

Each method returns `void`. They communicate results through exceptions only — nothing is returned on success and an exception is thrown on failure:

* `GuardianValidationException` — the transition check failed and no custom exception was provided
* the provided custom exception — the check failed and a custom exception was passed
* `GuardianExecutingRuleException` — the rule could not be executed

---

## 🏛️ Architecture

This package is a small shortcut layer over the Aegisora validation pipeline.

Flow:

1. `StateTransitionRuleGuardian` is called with a transition and the allowed transition maps, plus an optional exception
2. A `StateTransitionRule` is created (`create()`)
3. `Guardian` executes the rule against the transition
4. If the check passes, execution continues normally
5. If the check fails, the custom exception or `GuardianValidationException` is thrown
6. If the rule could not be executed, `GuardianExecutingRuleException` is thrown

Internal flow:

```
transition → StateTransitionRuleGuardian → Guardian → StateTransitionRule → Result → Exception
```

---

## 🔗 Related Packages

* [aegisora/guardian](https://github.com/Aegisora/guardian) — validation execution orchestrator
* [aegisora/state-transition-rule](https://github.com/Aegisora/state-transition-rule) — state transition rule
* [aegisora/rule-contract](https://github.com/Aegisora/rule-contract) — base rule contract and validation result architecture

---

## ⚖️ License

This package is open-source and licensed under the MIT License. See the LICENSE for details.

---

## 🌱 Contributing

Contributions are welcome and greatly appreciated!. See the CONTRIBUTING for details.

---

## 🌟 Support

If you find this project useful, please consider giving it a star on GitHub!

It helps the project grow and motivates further development.
