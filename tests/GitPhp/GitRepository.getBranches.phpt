<?php

use Tester\Assert;
use Penobit\Git\Git;
use Penobit\Git\GitException;
use Penobit\Git\Runners\MemoryRunner;

require __DIR__ . '/bootstrap.php';

$runner = new MemoryRunner(__DIR__);
$git = new Git($runner);
$repo = $git->open(__DIR__);

$runner->setResult(['branch', '-a', '--no-color'], [], [
	'  master',
	'* develop',
	'  remotes/origin/master',
]);
Assert::same([
	'master',
	'develop',
	'remotes/origin/master',
], $repo->getBranches());


$runner->setResult(['branch', '-a', '--no-color'], [], []);
Assert::null($repo->getBranches());
