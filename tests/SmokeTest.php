<?php declare(strict_types=1);

use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Version;

final class SmokeTest extends TestCase
{
    public function testPhpunitRuns(): void
    {
        $this->assertNotEmpty(Version::id());
    }

    public function testSrcNamespaceIsRegisteredForPsr4Autoload(): void
    {
        $prefixes = [];

        foreach (ClassLoader::getRegisteredLoaders() as $loader) {
            $prefixes += $loader->getPrefixesPsr4();
        }

        $this->assertArrayHasKey('Src\\', $prefixes);

        $resolvedPaths = array_map(realpath(...), $prefixes['Src\\']);

        $this->assertContains(realpath(dirname(__DIR__)), $resolvedPaths);
    }
}
