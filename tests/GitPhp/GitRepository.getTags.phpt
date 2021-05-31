<?php

use Tester\Assert;
use Penobit\Git\Git;
use Penobit\Git\Runners\MemoryRunner;

require __DIR__ . '/bootstrap.php';

$runner = new MemoryRunner(__DIR__);
$git = new Git($runner);
$repo = $git->open(__DIR__);

$runner->setResult(['tag'], [], [
	' v1.0.0 ',
	'v1.0.1',
	'v1.0.2',
	'v2.0.0',
	'v3.0.0',
	'v3.1.0',
]);
Assert::same([
	'v1.0.0',
	'v1.0.1',
	'v1.0.2',
	'v2.0.0',
	'v3.0.0',
	'v3.1.0',
], $repo->getTags());
