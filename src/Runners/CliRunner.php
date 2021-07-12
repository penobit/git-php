<?php

namespace Penobit\Git\Runners;

    use Penobit\Git\CommandProcessor;
    use Penobit\Git\GitException;
    use Penobit\Git\IRunner;
    use Penobit\Git\RunnerResult;

    class CliRunner implements IRunner {
        /** @var string */
        private $gitBinary;

        /** @var CommandProcessor */
        private $commandProcessor;

        /**
         * @param string $gitBinary
         */
        public function __construct($gitBinary = 'git') {
            $this->gitBinary = $gitBinary;
            $this->commandProcessor = new CommandProcessor();
        }

        /**
         * @return RunnerResult
         */
        public function run($cwd, array $args, array $env = null) {
            if (!is_dir($cwd)) {
                throw new GitException("Directory '{$cwd}' not found");
            }

            $descriptorspec = [
                0 => ['pipe', 'r'], // stdin
                1 => ['pipe', 'w'], // stdout
                2 => ['pipe', 'w'], // stderr
            ];

            $pipes = [];
            $command = $this->commandProcessor->process($this->gitBinary, $args);
            $process = proc_open($command, $descriptorspec, $pipes, $cwd, $env, [
                'bypass_shell' => true,
            ]);

            if (!$process) {
                throw new GitException("Executing of command '{$command}' failed (directory {$cwd}).");
            }

            // Reset output and error
            stream_set_blocking($pipes[1], false);
            stream_set_blocking($pipes[2], false);
            $stdout = '';
            $stderr = '';

            while (true) {
                // Read standard output
                $stdoutOutput = stream_get_contents($pipes[1]);

                if (\is_string($stdoutOutput)) {
                    $stdout .= $stdoutOutput;
                }

                // Read error output
                $stderrOutput = stream_get_contents($pipes[2]);

                if (\is_string($stderrOutput)) {
                    $stderr .= $stderrOutput;
                }

                // We are done
                if ((feof($pipes[1]) || false === $stdoutOutput) && (feof($pipes[2]) || false === $stderrOutput)) {
                    break;
                }
            }

            $returnCode = proc_close($process);

            return new RunnerResult($command, $returnCode, $this->convertOutput($stdout), $this->convertOutput($stderr));
        }

        /**
         * @return string
         */
        public function getCwd() {
            $cwd = getcwd();

            if (!\is_string($cwd)) {
                throw new \Penobit\Git\InvalidStateException('Getting of CWD failed.');
            }

            return $cwd;
        }

        /**
         * @param string $output
         *
         * @return string[]
         */
        protected function convertOutput($output) {
            $output = str_replace(["\r\n", "\r"], "\n", $output);
            $output = rtrim($output, "\n");

            if ('' === $output) {
                return [];
            }

            return explode("\n", $output);
        }
    }
