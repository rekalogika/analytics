<?php

use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\Tests\App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__ . '/../.env');

$env = $_SERVER['APP_ENV'] ?? 'dev';

if (!is_string($env)) {
    throw new RuntimeException('The APP_ENV environment variable must be a string.');
}

$debug = boolval($_SERVER['APP_DEBUG'] ?? '0');

$kernel = new Kernel($env, $debug);
$kernel->boot();

$doctrine = $kernel->getContainer()->get('doctrine');

if (!$doctrine instanceof ManagerRegistry) {
    throw new RuntimeException('The Doctrine service is not available.');
}

return $doctrine->getManager();