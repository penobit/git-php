<?php

use Tester\Assert;
use Penobit\Git\CommitId;
use Penobit\Git\InvalidArgumentException;

require __DIR__ . '/bootstrap.php';

$commitId = new CommitId('734713bc047d87bf7eac9674765ae793478c50d3');
Assert::same('734713bc047d87bf7eac9674765ae793478c50d3', (string) $commitId);
Assert::same('734713bc047d87bf7eac9674765ae793478c50d3', $commitId->toString());


Assert::exception(function () {
	new CommitId('test');

}, InvalidArgumentException::class, "Invalid commit ID 'test'.");


Assert::exception(function () {
	new CommitId([]);

}, InvalidArgumentException::class, "Invalid commit ID, expected string, array given.");
