<?php
namespace Cognesy\Instructor\Utils;

use Throwable;

/**
 * Pipeline class is a utility class that allows you to process a payload through a series of callable processors.
 * It also provides hooks for executing callbacks before and after each processor, as well as error handling.
 *
 * ## Example #1:
 *
 * return (new Pipeline())
 *  ->through([
 *    fn ($payload) => $payload + 1,
 *    fn ($payload) => $payload * 2,
 *    fn ($payload) => $payload - 3,
 * ])->process(5);
 *
 *
 * ## Example #2:
 *
 * $pipeline = Pipeline::through([
 *    fn ($payload) => $payload + 1,
 *    fn ($payload) => $payload * 2,
 *    fn ($payload) => $payload - 3,
 * ])->beforeEach(fn ($payload) {
 *    echo "Before: $payload\n";
 * })->afterEach(function ($payload) {
 *    echo "After: $payload\n";
 * })->finishWhen(function ($payload) {
 *    return ($payload < 0);
 * })->onError(function (Throwable $e) {
 *    echo $e->getMessage();
 * })->then(function ($payload) {
 *    return $payload * 2;
 * });
 *
 * return $pipeline->process(5);
 */

class Pipeline {
    /**
     * @var callable[] The processors that the payload will be passed through.
     */
    private $processors = [];
    /**
     * @var callable|null The callbacks used to decide if processing is done.
     */
    private $isDoneCall = [];
    /**
     * @var callable[] The callbacks to be executed before each processor.
     */
    private $beforeCalls = [];
    /**
     * @var callable[] The callbacks to be executed after each processor.
     */
    private $afterCalls = [];
    /**
     * @var callable|null The callback to be executed if an error occurs during processing.
     */
    private $onErrorCall;
    /**
     * @var callable|null The callback to be executed after all processors have been run.
     */
    private $thenCall;

    /**
     * Pipeline constructor.
     *
     * @param callable[] $processors The initial set of processors.
     */
    public function __construct(array $processors = []) {
        $this->processors = $processors;
    }

    /**
     * Set the processors for the pipeline.
     *
     * @param callable[] $processors The processors to be used.
     * @return self
     */
    public function through(mixed $processors) : self {
        if (is_array($processors)) {
            $this->processors = $processors;
        } else {
            $this->processors[] = $processors;
        }
        return $this;
    }

    /**
     * Process the payload through the pipeline.
     *
     * @param mixed $payload The payload to be processed.
     * @return mixed The processed payload.
     * @throws Throwable If an error occurs during processing and no error callback is set.
     */
    public function process(mixed $payload) : mixed {
        $carry = $payload;
        try {
            foreach ($this->processors as $pipe) {
                $carry = $this->executeCallbacks($carry, $this->beforeCalls);
                $carry = $pipe($carry);
                if ($carry === null) {
                    break;
                }
                $carry = $this->executeCallbacks($carry, $this->afterCalls);
                if ($this->isDoneCall) {
                    foreach ($this->isDoneCall as $callback) {
                        if ($callback($carry)) {
                            break 2;
                        }
                    }
                }
            }
            if ($this->thenCall) {
                $callback = $this->thenCall;
                $carry = $callback($carry);
            }
            return $carry;
        } catch (Throwable $e) {
            if ($this->onErrorCall) {
                $callback = $this->onErrorCall;
                return $callback($e);
            }
            throw $e;
        }
    }

    /**
     * Add a callback to be executed before each processor.
     *
     * @param callable $callback The callback to be added.
     * @return self
     */
    public function beforeEach(callable $callback) : self {
        $this->beforeCalls[] = $callback;
        return $this;
    }

    /**
     * Add a callback to be executed after each processor.
     *
     * @param callable $callback The callback to be added.
     * @return self
     */
    public function afterEach(callable $callback) : self {
        $this->afterCalls[] = $callback;
        return $this;
    }

    /**
     * Add a callback to be executed after each processor.
     *
     * @param callable $callback The callback to be added.
     * @return self
     */
    public function onError(callable $callback) : self {
        $this->onErrorCall = $callback;
        return $this;
    }

    /**
     * Add a callback to be executed to determine if processing is done.
     *
     * @param callable $callback The callback to be used.
     * @return self
     */
    public function finishWhen(callable $callback) : self {
        $this->isDoneCall[] = $callback;
        return $this;
    }

    /**
     * Set the callback to be executed after all processors have been run.
     *
     * @param callable $callback The final callback.
     * @return self
     */
    public function then(callable $callback) : self {
        $this->thenCall = $callback;
        return $this;
    }

    /**
     * Execute a series of callbacks with a given payload.
     *
     * @param mixed $carry The payload to be passed to the callbacks.
     * @param callable[] $callbacks The callbacks to be executed.
     * @return mixed The modified payload.
     */
    private function executeCallbacks($carry, array $callbacks) {
        foreach ($callbacks as $callback) {
            $result = $callback($carry);
            if ($result !== null) {
                $carry = $result;
            }
        }
        return $carry;
    }
}
