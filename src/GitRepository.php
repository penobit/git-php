<?php

namespace Penobit\Git;

    class GitRepository {
        /** @var string */
        protected $repository;

        /** @var IRunner */
        protected $runner;

        /**
         * @param string $repository
         *
         * @throws GitException
         */
        public function __construct($repository, IRunner $runner = null) {
            if ('.git' === basename($repository)) {
                $repository = \dirname($repository);
            }

            $path = realpath($repository);

            if (false === $path) {
                throw new GitException("Repository '{$repository}' not found.");
            }

            $this->repository = $path;
            $this->runner = null !== $runner ? $runner : new Runners\CliRunner();
        }

        /**
         * @return string
         */
        public function getRepositoryPath() {
            return $this->repository;
        }

        /**
         * Creates a tag.
         * `git tag <name>`.
         *
         * @param string $name
         * @param null|array<mixed> $options
         *
         * @throws GitException
         *
         * @return static
         */
        public function createTag($name, $options = null) {
            $this->run('tag', $options, $name);

            return $this;
        }

        /**
         * Removes tag.
         * `git tag -d <name>`.
         *
         * @param string $name
         *
         * @throws GitException
         *
         * @return static
         */
        public function removeTag($name) {
            $this->run('tag', [
                '-d' => $name,
            ]);

            return $this;
        }

        /**
         * Renames tag.
         * `git tag <new> <old>`
         * `git tag -d <old>`.
         *
         * @param string $oldName
         * @param string $newName
         *
         * @throws GitException
         *
         * @return static
         */
        public function renameTag($oldName, $newName) {
            // http://stackoverflow.com/a/1873932
            // create new as alias to old (`git tag NEW OLD`)
            $this->run('tag', $newName, $oldName);
            // delete old (`git tag -d OLD`)
            $this->removeTag($oldName);

            return $this;
        }

        /**
         * Returns list of tags in repo.
         *
         * @throws GitException
         *
         * @return null|string[] NULL => no tags
         */
        public function getTags() {
            return $this->extractFromCommand(['tag'], 'trim');
        }

        /**
         * Merges branches.
         * `git merge <options> <name>`.
         *
         * @param string $branch
         * @param null|array<mixed> $options
         *
         * @throws GitException
         *
         * @return static
         */
        public function merge($branch, $options = null) {
            $this->run('merge', $options, $branch);

            return $this;
        }

        /**
         * Creates new branch.
         * `git branch <name>`
         * (optionaly) `git checkout <name>`.
         *
         * @param string $name
         * @param bool $checkout
         *
         * @throws GitException
         *
         * @return static
         */
        public function createBranch($name, $checkout = false) {
            // git branch $name
            $this->run('branch', $name);

            if ($checkout) {
                $this->checkout($name);
            }

            return $this;
        }

        /**
         * Removes branch.
         * `git branch -d <name>`.
         *
         * @param string $name
         *
         * @throws GitException
         *
         * @return static
         */
        public function removeBranch($name) {
            $this->run('branch', [
                '-d' => $name,
            ]);

            return $this;
        }

        /**
         * Gets name of current branch
         * `git branch` + magic.
         *
         * @throws GitException
         *
         * @return string
         */
        public function getCurrentBranchName() {
            try {
                $branch = $this->extractFromCommand(['branch', '-a', '--no-color'], function($value) {
                    if (isset($value[0]) && '*' === $value[0]) {
                        return trim(substr($value, 1));
                    }

                    return false;
                });

                if (\is_array($branch)) {
                    return $branch[0];
                }
            } catch (GitException $e) {
                // nothing
            }

            throw new GitException('Getting of current branch name failed.');
        }

        /**
         * Returns list of all (local & remote) branches in repo.
         *
         * @throws GitException
         *
         * @return null|string[] NULL => no branches
         */
        public function getBranches() {
            return $this->extractFromCommand(['branch', '-a', '--no-color'], function($value) {
                return trim(substr($value, 1));
            });
        }

        /**
         * Returns list of remote branches in repo.
         *
         * @throws GitException
         *
         * @return null|string[] NULL => no branches
         */
        public function getRemoteBranches() {
            return $this->extractFromCommand(['branch', '-r', '--no-color'], function($value) {
                return trim(substr($value, 1));
            });
        }

        /**
         * Returns list of local branches in repo.
         *
         * @throws GitException
         *
         * @return null|string[] NULL => no branches
         */
        public function getLocalBranches() {
            return $this->extractFromCommand(['branch', '--no-color'], function($value) {
                return trim(substr($value, 1));
            });
        }

        /**
         * Checkout branch.
         * `git checkout <branch>`.
         *
         * @param string $name
         *
         * @throws GitException
         *
         * @return static
         */
        public function checkout($name) {
            $this->run('checkout', $name);

            return $this;
        }

        /**
         * Removes file(s).
         * `git rm <file>`.
         *
         * @param string|string[] $file
         *
         * @throws GitException
         *
         * @return static
         */
        public function removeFile($file) {
            if (!\is_array($file)) {
                $file = \func_get_args();
            }

            foreach ($file as $item) {
                $this->run('rm', $item, '-r');
            }

            return $this;
        }

        /**
         * Adds file(s).
         * `git add <file>`.
         *
         * @param string|string[] $file
         *
         * @throws GitException
         *
         * @return static
         */
        public function addFile($file) {
            if (!\is_array($file)) {
                $file = \func_get_args();
            }

            foreach ($file as $item) {
                // make sure the given item exists
                // this can be a file or an directory, git supports both
                $path = Helpers::isAbsolute($item) ? $item : ($this->getRepositoryPath().\DIRECTORY_SEPARATOR.$item);

                if (!file_exists($path)) {
                    throw new GitException("The path at '{$item}' does not represent a valid file.");
                }

                $this->run('add', $item);
            }

            return $this;
        }

        /**
         * Adds all created, modified & removed files.
         * `git add --all`.
         *
         * @throws GitException
         *
         * @return static
         */
        public function addAllChanges() {
            $this->run('add', '--all');

            return $this;
        }

        /**
         * Renames file(s).
         * `git mv <file>`.
         *
         * @param string|string[] $file from: array('from' => 'to', ...) || (from, to)
         * @param null|string $to
         *
         * @throws GitException
         *
         * @return static
         */
        public function renameFile($file, $to = null) {
            if (!\is_array($file)) { // rename(file, to);
                $file = [
                    $file => $to,
                ];
            }

            foreach ($file as $from => $to) {
                $this->run('mv', $from, $to);
            }

            return $this;
        }

        /**
         * Commits changes
         * `git commit <params> -m <message>`.
         *
         * @param string $message
         * @param string[] $params param => value
         *
         * @throws GitException
         *
         * @return static
         */
        public function commit($message, $params = null) {
            $this->run('commit', $params, [
                '-m' => $message,
            ]);

            return $this;
        }

        /**
         * Returns last commit ID on current branch
         * `git log --pretty=format:"%H" -n 1`.
         *
         * @throws GitException
         *
         * @return CommitId
         */
        public function getLastCommitId() {
            $result = $this->run('log', '--pretty=format:"%H"', '-n', '1');
            $lastLine = $result->getOutputLastLine();

            return new CommitId((string) $lastLine);
        }

        /**
         * @return Commit
         */
        public function getLastCommit() {
            return $this->getCommit($this->getLastCommitId());
        }

        /**
         * @param CommitId|string $commitId
         *
         * @return Commit
         */
        public function getCommit($commitId) {
            if (!($commitId instanceof CommitId)) {
                $commitId = new CommitId($commitId);
            }

            // subject
            $result = $this->run('log', '-1', $commitId, '--format="%s"');
            $subject = rtrim($result->getOutputAsString());

            // body
            $result = $this->run('log', '-1', $commitId, '--format="%b"');
            $body = rtrim($result->getOutputAsString());

            // author email
            $result = $this->run('log', '-1', $commitId, '--format="%ae"');
            $authorEmail = rtrim($result->getOutputAsString());

            // author name
            $result = $this->run('log', '-1', $commitId, '--format="%an"');
            $authorName = rtrim($result->getOutputAsString());

            // author date
            $result = $this->run('log', '-1', $commitId, '--pretty="format:%ad"', '--date=iso-strict');
            $authorDate = \DateTimeImmutable::createFromFormat(\DateTime::ATOM, (string) $result->getOutputLastLine());

            if (!($authorDate instanceof \DateTimeImmutable)) {
                throw new GitException('Failed fetching of commit author date.', 0, null, $result);
            }

            // committer email
            $result = $this->run('log', '-1', $commitId, '--format="%ce"');
            $committerEmail = rtrim($result->getOutputAsString());

            // committer name
            $result = $this->run('log', '-1', $commitId, '--format="%cn"');
            $committerName = rtrim($result->getOutputAsString());

            // committer date
            $result = $this->run('log', '-1', $commitId, '--pretty="format:%cd"', '--date=iso-strict');
            $committerDate = \DateTimeImmutable::createFromFormat(\DateTime::ATOM, (string) $result->getOutputLastLine());

            if (!($committerDate instanceof \DateTimeImmutable)) {
                throw new GitException('Failed fetching of commit committer date.', 0, null, $result);
            }

            return new Commit(
                $commitId,
                $subject,
                '' !== $body ? $body : null,
                $authorEmail,
                '' !== $authorName ? $authorName : null,
                $authorDate,
                $committerEmail,
                '' !== $committerName ? $committerName : null,
                $committerDate
            );
        }

        /**
         * Exists changes?
         * `git status` + magic.
         *
         * @throws GitException
         *
         * @return bool
         */
        public function hasChanges() {
            // Make sure the `git status` gets a refreshed look at the working tree.
            $this->run('update-index', '-q', '--refresh');
            $result = $this->run('status', '--porcelain');

            return $result->hasOutput();
        }

        /**
         * Pull changes from a remote.
         *
         * @param null|string $remote
         * @param null|array<mixed> $params
         *
         * @throws GitException
         *
         * @return static
         */
        public function pull($remote = null, array $params = null) {
            $this->run('pull', $remote, $params);

            return $this;
        }

        /**
         * Push changes to a remote.
         *
         * @param null|string $remote
         * @param null|array<mixed> $params
         *
         * @throws GitException
         *
         * @return static
         */
        public function push($remote = null, array $params = null) {
            $this->run('push', $remote, $params);

            return $this;
        }

        /**
         * Run fetch command to get latest branches.
         *
         * @param null|string $remote
         * @param null|array<mixed> $params
         *
         * @throws GitException
         *
         * @return static
         */
        public function fetch($remote = null, array $params = null) {
            $this->run('fetch', $remote, $params);

            return $this;
        }

        /**
         * Adds new remote repository.
         *
         * @param string $name
         * @param string $url
         * @param null|array<mixed> $params
         *
         * @throws GitException
         *
         * @return static
         */
        public function addRemote($name, $url, array $params = null) {
            $this->run('remote', 'add', $params, $name, $url);

            return $this;
        }

        /**
         * Renames remote repository.
         *
         * @param string $oldName
         * @param string $newName
         *
         * @throws GitException
         *
         * @return static
         */
        public function renameRemote($oldName, $newName) {
            $this->run('remote', 'rename', $oldName, $newName);

            return $this;
        }

        /**
         * Removes remote repository.
         *
         * @param string $name
         *
         * @throws GitException
         *
         * @return static
         */
        public function removeRemote($name) {
            $this->run('remote', 'remove', $name);

            return $this;
        }

        /**
         * Changes remote repository URL.
         *
         * @param string $name
         * @param string $url
         * @param null|array<mixed> $params
         *
         * @throws GitException
         *
         * @return static
         */
        public function setRemoteUrl($name, $url, array $params = null) {
            $this->run('remote', 'set-url', $params, $name, $url);

            return $this;
        }

        /**
         * @param mixed ...$cmd
         *
         * @throws GitException
         *
         * @return string[] returns output
         */
        public function execute(...$cmd) {
            $result = $this->run(...$cmd);

            return $result->getOutput();
        }

        /**
         * @param array<mixed> $args
         *
         * @throws GitException
         *
         * @return null|string[]
         */
        protected function extractFromCommand(array $args, callable $filter = null) {
            $result = $this->run(...$args);
            $output = $result->getOutput();

            if (null !== $filter) {
                $newArray = [];

                foreach ($output as $line) {
                    $value = $filter($line);

                    if (false === $value) {
                        continue;
                    }

                    $newArray[] = (string) $value;
                }

                $output = $newArray;
            }

            if (empty($output)) {
                return null;
            }

            return $output;
        }

        /**
         * Runs command.
         *
         * @param mixed ...$args
         *
         * @throws GitException
         *
         * @return RunnerResult
         */
        protected function run(...$args) {
            $result = $this->runner->run($this->repository, $args);

            if (!$result->isOk()) {
                throw new GitException("Command '{$result->getCommand()}' failed (exit-code {$result->getExitCode()}).", $result->getExitCode(), null, $result);
            }

            return $result;
        }
    }
