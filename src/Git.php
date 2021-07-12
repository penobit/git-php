<?php

namespace Penobit\Git;

    class Git {
        /** @var IRunner */
        protected $runner;

        public function __construct(IRunner $runner = null) {
            $this->runner = null !== $runner ? $runner : new Runners\CliRunner();
        }

        /**
         * @param string $directory
         *
         * @return GitRepository
         */
        public function open($directory) {
            return new GitRepository($directory, $this->runner);
        }

        /**
         * Init repo in directory.
         *
         * @param string $directory
         * @param null|array<mixed> $params
         *
         * @throws GitException
         *
         * @return GitRepository
         */
        public function init($directory, array $params = null) {
            if (is_dir("{$directory}/.git")) {
                throw new GitException("Repo already exists in {$directory}.");
            }

            if (!is_dir($directory) && !@mkdir($directory, 0777, true)) { // intentionally @; not atomic; from Nette FW
                throw new GitException("Unable to create directory '{$directory}'.");
            }

            try {
                $this->run($directory, [
                    'init',
                    $params,
                    $directory,
                ]);
            } catch (GitException $e) {
                throw new GitException("Git init failed (directory {$directory}).", $e->getCode(), $e);
            }

            return $this->open($directory);
        }

        /**
         * Clones GIT repository from $url into $directory.
         *
         * @param string $url
         * @param null|string $directory
         * @param null|array<mixed> $params
         *
         * @throws GitException
         *
         * @return GitRepository
         */
        public function cloneRepository($url, $directory = null, array $params = null) {
            if (null !== $directory && is_dir("{$directory}/.git")) {
                throw new GitException("Repo already exists in {$directory}.");
            }

            $cwd = $this->runner->getCwd();

            if (null === $directory) {
                $directory = Helpers::extractRepositoryNameFromUrl($url);
                $directory = "{$cwd}/{$directory}";
            } elseif (!Helpers::isAbsolute($directory)) {
                $directory = "{$cwd}/{$directory}";
            }

            if (null === $params) {
                $params = '-q';
            }

            try {
                $this->run($cwd, [
                    'clone',
                    $params,
                    $url,
                    $directory,
                ]);
            } catch (GitException $e) {
                $stderr = '';
                $result = $e->getRunnerResult();

                if (null !== $result && $result->hasErrorOutput()) {
                    $stderr = implode(PHP_EOL, $result->getErrorOutput());
                }

                throw new GitException("Git clone failed (directory {$directory}).".('' !== $stderr ? ("\n{$stderr}") : ''));
            }

            return $this->open($directory);
        }

        /**
         * @param string $url
         * @param null|array<string> $refs
         *
         * @return bool
         */
        public function isRemoteUrlReadable($url, array $refs = null) {
            $result = $this->runner->run($this->runner->getCwd(), [
                'ls-remote',
                '--heads',
                '--quiet',
                '--exit-code',
                $url,
                $refs,
            ], [
                'GIT_TERMINAL_PROMPT' => 0,
            ]);

            return $result->isOk();
        }

        /**
         * @param string $cwd
         * @param array<mixed> $args
         * @param array<string, scalar> $env
         *
         * @throws GitException
         *
         * @return RunnerResult
         */
        private function run($cwd, array $args, array $env = null) {
            $result = $this->runner->run($cwd, $args, $env);

            if (!$result->isOk()) {
                throw new GitException("Command '{$result->getCommand()}' failed (exit-code {$result->getExitCode()}).", $result->getExitCode(), null, $result);
            }

            return $result;
        }
    }
