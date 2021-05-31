<?php

use Tester\Assert;
use Penobit\Git\Git;
use Penobit\Git\GitException;
use Penobit\Git\Tests\AssertRunner;

require __DIR__ . '/bootstrap.php';

$runner = new AssertRunner(__DIR__);
$git = new Git($runner);

$runner->assert(['branch', 'master']);
$runner->assert(['branch', 'develop']);
$runner->assert(['checkout', 'develop']);
$runner->assert(['merge', 'feature-1']);
$runner->assert(['branch', '-d', 'feature-1']);
$runner->assert(['checkout', 'master']);

$repo = $git->open(__DIR__);
$repo->createBranch('master');
$repo->createBranch('develop', TRUE);
$repo->merge('feature-1');
$repo->removeBranch('feature-1');
$repo->checkout('master');
