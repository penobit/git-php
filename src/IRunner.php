<?php

namespace Penobit\Git;

    interface IRunner {
        /**
         * @param string $cwd
         * @param array<mixed> $args
         * @param null|array<string, scalar> $env
         *
         * @return RunnerResult
         */
        public function run($cwd, array $args, array $env = null);

        /**
         * @return string
         */
        public function getCwd();
    }
