#!/bin/sh 

#phpunit --bootstrap tests/bootstrap.php --filter UrlInfoTest::testAbsolute tests

phpunit --bootstrap tests/bootstrap.php tests
