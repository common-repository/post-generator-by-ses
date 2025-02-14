<?php
namespace GuzzleHttp\Promise;
class EachPromise implements PromisorInterface
{
    private $pending = [];
    private $iterable;
    private $concurrency;
    private $onFulfilled;
    private $onRejected;
    private $aggregate;
    private $mutex;
    public function __construct($iterable, array $config = [])
    {
        $this->iterable = iter_for($iterable);
        if (isset($config['concurrency'])) {
            $this->concurrency = $config['concurrency'];
        }
        if (isset($config['fulfilled'])) {
            $this->onFulfilled = $config['fulfilled'];
        }
        if (isset($config['rejected'])) {
            $this->onRejected = $config['rejected'];
        }
    }
    public function promise()
    {
        if ($this->aggregate) {
            return $this->aggregate;
        }
        try {
            $this->createPromise();
            $this->iterable->rewind();
            if (!$this->checkIfFinished()) {
                $this->refillPending();
            }
        } catch (\Throwable $e) {
            $this->aggregate->reject($e);
        } catch (\Exception $e) {
            $this->aggregate->reject($e);
        }
        return $this->aggregate;
    }
    private function createPromise()
    {
        $this->mutex = false;
        $this->aggregate = new Promise(function () {
            reset($this->pending);
            while ($promise = current($this->pending)) {
                next($this->pending);
                $promise->wait();
                if ($this->aggregate->getState() !== PromiseInterface::PENDING) {
                    return;
                }
            }
        });
        $clearFn = function () {
            $this->iterable = $this->concurrency = $this->pending = null;
            $this->onFulfilled = $this->onRejected = null;
        };
        $this->aggregate->then($clearFn, $clearFn);
    }
    private function refillPending()
    {
        if (!$this->concurrency) {
            while ($this->addPending() && $this->advanceIterator());
            return;
        }
        $concurrency = is_callable($this->concurrency)
            ? call_user_func($this->concurrency, count($this->pending))
            : $this->concurrency;
        $concurrency = max($concurrency - count($this->pending), 0);
        if (!$concurrency) {
            return;
        }
        $this->addPending();
        while (--$concurrency
            && $this->advanceIterator()
            && $this->addPending());
    }
    private function addPending()
    {
        if (!$this->iterable || !$this->iterable->valid()) {
            return false;
        }
        $promise = promise_for($this->iterable->current());
        $key = $this->iterable->key();
        $this->pending[] = null;
        end($this->pending);
        $idx = key($this->pending);
        $this->pending[$idx] = $promise->then(
            function ($value) use ($idx, $key) {
                if ($this->onFulfilled) {
                    call_user_func(
                        $this->onFulfilled,
                        $value,
                        $key,
                        $this->aggregate
                    );
                }
                $this->step($idx);
            },
            function ($reason) use ($idx, $key) {
                if ($this->onRejected) {
                    call_user_func(
                        $this->onRejected,
                        $reason,
                        $key,
                        $this->aggregate
                    );
                }
                $this->step($idx);
            }
        );
        return true;
    }
    private function advanceIterator()
    {
        if ($this->mutex) {
            return false;
        }
        $this->mutex = true;
        try {
            $this->iterable->next();
            $this->mutex = false;
            return true;
        } catch (\Throwable $e) {
            $this->aggregate->reject($e);
            $this->mutex = false;
            return false;
        } catch (\Exception $e) {
            $this->aggregate->reject($e);
            $this->mutex = false;
            return false;
        }
    }
    private function step($idx)
    {
        if ($this->aggregate->getState() !== PromiseInterface::PENDING) {
            return;
        }
        unset($this->pending[$idx]);
        if ($this->advanceIterator() && !$this->checkIfFinished()) {
            $this->refillPending();
        }
    }
    private function checkIfFinished()
    {
        if (!$this->pending && !$this->iterable->valid()) {
            $this->aggregate->resolve(null);
            return true;
        }
        return false;
    }
}
