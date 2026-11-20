<?php

use PHPUnit\Framework\TestCase;

class LoginSecurityTest extends TestCase
{
    public function testGetLoginAttemptReturnsZeroWhenNoRow(): void
    {
        $db = new FakeLoginSecurityDb([]);
        $attempt = getLoginAttempt($db, 'user', '1.2.3.4');
        $this->assertSame(0, $attempt['attempt_count']);
        $this->assertSame(0, $attempt['lock_until_ts']);
    }

    public function testGetLoginAttemptParsesLockUntil(): void
    {
        $row = (object) ['attempt_count' => 4, 'lock_until' => date('Y-m-d H:i:s', time() + 900)];
        $db = new FakeLoginSecurityDb([$row]);
        $attempt = getLoginAttempt($db, 'user', '1.2.3.4');
        $this->assertSame(4, $attempt['attempt_count']);
        $this->assertGreaterThan(time(), $attempt['lock_until_ts']);
    }

    public function testGetLoginAttemptIgnoresPastOrInvalidLockUntil(): void
    {
        $db = new FakeLoginSecurityDb([(object) ['attempt_count' => 5, 'lock_until' => 'banana']]);
        $attempt = getLoginAttempt($db, 'user', '1.2.3.4');
        $this->assertSame(5, $attempt['attempt_count']);
        $this->assertSame(0, $attempt['lock_until_ts']);
    }

    public function testRegisterLoginFailureIncrementsAndLocksAtMax(): void
    {
        $db = new FakeLoginSecurityDb([]);
        $result = registerLoginFailure($db, 'user', '1.2.3.4', 4, 5);
        $this->assertSame(5, $result['attempt_count']);
        $this->assertTrue($result['locked']);

        $insert = $db->inserts[0];
        $this->assertSame(5, $insert[':attempt_count']);
        $this->assertNotNull($insert[':lock_until']);
        $this->assertGreaterThan(time(), strtotime($insert[':lock_until']));
    }

    public function testRegisterLoginFailureDoesNotLockBelowMax(): void
    {
        $db = new FakeLoginSecurityDb([]);
        $result = registerLoginFailure($db, 'user', '1.2.3.4', 2, 5);
        $this->assertSame(3, $result['attempt_count']);
        $this->assertFalse($result['locked']);
        $this->assertNull($db->inserts[0][':lock_until']);
    }

    public function testClearLoginAttemptsDeletesByUsuarioAndIp(): void
    {
        $db = new FakeLoginSecurityDb([]);
        clearLoginAttempts($db, 'user', '1.2.3.4');
        $this->assertSame(1, count($db->deletes));
        $this->assertSame('user', $db->deletes[0][':usuario']);
        $this->assertSame('1.2.3.4', $db->deletes[0][':ip']);
    }

    public function testCleanupOldLoginAttemptsDeletesExpiredAndOldRows(): void
    {
        $db = new FakeLoginSecurityDb([]);
        cleanupOldLoginAttempts($db);
        $this->assertSame(1, count($db->deletes));
        $this->assertStringContainsString('lock_until < NOW()', $db->deletes[0]['sql']);
    }
}

class FakeLoginSecurityDb extends database
{
    public array $inserts = [];
    public array $deletes = [];

    public function __construct(private array $rows)
    {
    }

    public function select(string $sql, array $params = []): array
    {
        return ['status' => 'success', 'data' => $this->rows];
    }

    public function insert(string $sql, array $params = []): array
    {
        $this->inserts[] = $params;
        return ['status' => 'success'];
    }

    public function delete(string $sql, array $params = []): array
    {
        $this->deletes[] = ['sql' => $sql, ...$params];
        return ['status' => 'success'];
    }
}