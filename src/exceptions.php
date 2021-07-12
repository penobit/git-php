<?php

namespace Penobit\Git;

    class Exception extends \Exception {
    }

    class GitException extends Exception {
        /** @var null|RunnerResult */
        private $runnerResult;

        /**
         * @param string $message
         * @param int $code
         */
        public function __construct($message, $code = 0, \Exception $previous = null, RunnerResult $runnerResult = null) {
            parent::__construct($message, $code, $previous);
            $this->runnerResult = $runnerResult;
        }

        /**
         * @return null|RunnerResult
         */
        public function getRunnerResult() {
            return $this->runnerResult;
        }
    }

    class InvalidArgumentException extends Exception {
    }

    class InvalidStateException extends Exception {
    }

    class StaticClassException extends Exception {
    }
