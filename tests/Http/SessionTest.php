<?php

use KittyShare\Http\Session;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SessionTest extends TestCase
{
    protected function tearDown(): void
    {
        // The session stays active for the whole test class, mirroring
        // production where Session::configure() runs exactly once before
        // the session starts. Re-running it on an active session would
        // emit ini_set() warnings, so only the test keys are cleaned up.
        Session::remove('session_test_key', 'session_test_other');

        parent::tearDown();
    }

    #[Test]
    public function storesAndReadsValues(): void
    {
        Session::set('session_test_key', 'value');

        $this->assertSame('value', Session::get('session_test_key'));
    }

    #[Test]
    public function returnsDefaultForMissingKeys(): void
    {
        $this->assertNull(Session::get('session_test_key'));
        $this->assertSame('fallback', Session::get('session_test_key', 'fallback'));
    }

    #[Test]
    public function removesKeys(): void
    {
        Session::set('session_test_key', 'value');
        Session::set('session_test_other', 'other');

        Session::remove('session_test_key', 'session_test_other');

        $this->assertNull(Session::get('session_test_key'));
        $this->assertNull(Session::get('session_test_other'));
    }

    #[Test]
    public function pullsValueAndRemovesIt(): void
    {
        Session::set('session_test_key', 'value');

        $this->assertSame('value', Session::pull('session_test_key'));
        $this->assertNull(Session::get('session_test_key'));
    }

    #[Test]
    public function pullReturnsDefaultForMissingKeys(): void
    {
        $this->assertNull(Session::pull('session_test_key'));
        $this->assertSame('fallback', Session::pull('session_test_key', 'fallback'));
    }

    #[Test]
    public function regenerateKeepsSessionData(): void
    {
        Session::set('session_test_key', 'value');

        Session::regenerate();

        $this->assertSame('value', Session::get('session_test_key'));
        $this->assertNotSame('', session_id());
    }
}
