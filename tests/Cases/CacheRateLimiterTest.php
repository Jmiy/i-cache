<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace HyperfTest\Cases;

use Illuminate\Cache\Contracts\Repository as Cache;
use Illuminate\Cache\RateLimiter;
use Mockery as m;

/**
 * @internal
 * @coversNothing
 */
class CacheRateLimiterTest extends AbstractTestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testTooManyAttemptsReturnTrueIfAlreadyLockedOut()
    {
        $cache = m::mock(Cache::class);
        $cache->shouldReceive('get')->once()->with('key', 0)->andReturn(1);
        $cache->shouldReceive('has')->once()->with('key:timer')->andReturn(true);
        $cache->shouldReceive('add')->never();
        $rateLimiter = new RateLimiter($cache);

        $this->assertTrue($rateLimiter->tooManyAttempts('key', 1));
    }

    public function testHitProperlyIncrementsAttemptCount()
    {
        $cache = m::mock(Cache::class);
        $cache->shouldReceive('add')->once()->with('key:timer', m::type('int'), 1)->andReturn(true);
        $cache->shouldReceive('add')->once()->with('key', 0, 1)->andReturn(true);
        $cache->shouldReceive('increment')->once()->with('key')->andReturn(1);
        $rateLimiter = new RateLimiter($cache);

        $rateLimiter->hit('key', 1);
        $this->assertTrue(true);
    }

    public function testHitHasNoMemoryLeak()
    {
        $cache = m::mock(Cache::class);
        $cache->shouldReceive('add')->once()->with('key:timer', m::type('int'), 1)->andReturn(true);
        $cache->shouldReceive('add')->once()->with('key', 0, 1)->andReturn(false);
        $cache->shouldReceive('increment')->once()->with('key')->andReturn(1);
        $cache->shouldReceive('put')->once()->with('key', 1, 1);
        $rateLimiter = new RateLimiter($cache);

        $rateLimiter->hit('key', 1);
        $this->assertTrue(true);
    }

    public function testRetriesLeftReturnsCorrectCount()
    {
        $cache = m::mock(Cache::class);
        $cache->shouldReceive('get')->once()->with('key', 0)->andReturn(3);
        $rateLimiter = new RateLimiter($cache);

        $this->assertEquals(2, $rateLimiter->retriesLeft('key', 5));
    }

    public function testClearClearsTheCacheKeys()
    {
        $cache = m::mock(Cache::class);
        $cache->shouldReceive('forget')->once()->with('key');
        $cache->shouldReceive('forget')->once()->with('key:timer');
        $rateLimiter = new RateLimiter($cache);

        $rateLimiter->clear('key');
        $this->assertTrue(true);
    }
}
