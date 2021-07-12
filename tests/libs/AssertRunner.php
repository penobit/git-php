<?php

namespace Penobit\Git\Tests;

    use Penobit\Git\CommandProcessor;
    use Penobit\Git\RunnerResult;

    class AssertRunner implements \Penobit\Git\IRunner {
        /** @var string */
        private $cwd;

        /** @var CommandProcessor */
        private $commandProcessor;

        /** @var array [command => RunnerResult] */
        private $asserts = [];

        /**
         * @param string $cwd
         */
        public function __construct($cwd) {
            $this->cwd = $cwd;
            $this->commandProcessor = new CommandProcessor();
        }

        public function assert(array $expectedArgs, array $expectedEnv = [], array $resultOutput = [], array $resultErrorOutput = [], $resultExitCode = 0) {
            $cmd = $this->commandProcessor->process('git', $expectedArgs, $expectedEnv);
            $this->asserts[] = new RunnerResult($cmd, $resultExitCode, $resultOutput, $resultErrorOutput);

            return $this;
        }

        public function resetAsserts() {
            $this->asserts = [];

            return $this;
        }

        /**
         * @return RunnerResult
         */
        public function run($cwd, array $args, array $env = null) {
            if (empty($this->asserts)) {
                throw new \Penobit\Git\InvalidStateException('Missing asserts, use $runner->assert().');
            }

            $cmd = $this->commandProcessor->process('git', $args, $env);
            $result = current($this->asserts);

            if (!($result instanceof RunnerResult)) {
                throw new \Penobit\Git\InvalidStateException("Missing assert for command '{$cmd}'");
            }

            \Tester\Assert::same($cmd, $result->getCommand());
            next($this->asserts);

            return $result;
        }

        /**
         * @return string
         */
        public function getCwd() {
            return $this->cwd;
        }
    }
