<?php

declare(strict_types=1);

namespace SenderKit\Tests;

use PHPUnit\Framework\TestCase;
use SenderKit\Version;

final class VersionTest extends TestCase
{
    public function test_version_is_semver(): void
    {
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', Version::VALUE);
    }
}
