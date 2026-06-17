<?php

use Shift\Database\Attributes\Cast;
use Shift\Database\Attributes\Guarded;
use Shift\Database\Attributes\PrimaryKey;
use Shift\Database\Database;
use Shift\Database\DatabaseConfig;
use Shift\Database\Model;

final class TestProfileCast
{
    public function __construct(public readonly array $data)
    {
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function toArray(): array
    {
        return $this->data;
    }
}

final class TestUserRecord extends Model
{
    protected string $table = 'test_users';

    #[PrimaryKey]
    #[Cast('int')]
    public ?int $id = null;

    public string $email = '';

    #[Guarded]
    public string $role = 'user';

    #[Cast('array')]
    public array $meta = [];

    #[Cast('datetime')]
    public ?DateTimeImmutable $created_at = null;

    #[Cast(TestProfileCast::class)]
    public ?TestProfileCast $profile = null;
}

return [
    'query builder selects rows with fluent clauses' => function (): void {
        $db = makeModelDatabase();
        seedModelRows($db);

        $rows = $db->table('test_users')
            ->select('id', 'email')
            ->where('role', 'admin')
            ->orderBy('id', 'desc')
            ->limit(1)
            ->get();

        assertSameValue(1, count($rows), 'Query builder should respect where and limit clauses.');
        assertSameValue('second@example.com', $rows[0]['email'] ?? null, 'Query builder should order selected rows.');
    },
    'model query hydrates casts from database rows' => function (): void {
        $db = makeModelDatabase();
        seedModelRows($db);

        $user = TestUserRecord::query($db)->where('email', 'first@example.com')->first();

        assertSameValue(true, $user instanceof TestUserRecord, 'Model query should hydrate model instances.');
        assertSameValue(1, $user->id, 'Primary key should be cast to int.');
        assertSameValue(['tags' => ['api', 'db']], $user->meta, 'Array cast should decode JSON.');
        assertSameValue(true, $user->created_at instanceof DateTimeImmutable, 'Datetime cast should return DateTimeImmutable.');
        assertSameValue(true, $user->profile instanceof TestProfileCast, 'Class cast should hydrate value objects.');
        assertSameValue('Ada', $user->profile->data['name'] ?? null, 'Class cast should receive decoded data.');
    },
    'model create skips guarded input and explicit save can persist guarded fields' => function (): void {
        $db = makeModelDatabase();

        $user = TestUserRecord::create([
            'email' => 'guarded@example.com',
            'role' => 'admin',
            'meta' => ['source' => 'test'],
            'created_at' => new DateTimeImmutable('2026-06-17 10:00:00'),
            'profile' => new TestProfileCast(['name' => 'Grace']),
        ], $db);

        $stored = TestUserRecord::find($user->id, $db);
        assertSameValue('user', $stored->role, 'Guarded fields should not be mass assigned.');

        $updated = TestUserRecord::query($db)
            ->where('id', $user->id)
            ->update(['role' => 'admin']);

        assertSameValue(0, $updated, 'Guarded fields should not be updated through mass assignment.');

        $stored->role = 'admin';
        $stored->save($db);
        $reloaded = TestUserRecord::find($user->id, $db);

        assertSameValue('admin', $reloaded->role, 'Guarded fields can be persisted when set explicitly on the model.');
    },
    'model query supports find and delete' => function (): void {
        $db = makeModelDatabase();
        seedModelRows($db);

        $user = TestUserRecord::find(1, $db);
        $deleted = $user->delete($db);
        $missing = TestUserRecord::find(1, $db);

        assertSameValue(1, $deleted, 'Model delete should remove the current record.');
        assertSameValue(null, $missing, 'Deleted models should not be found.');
    },
];

function makeModelDatabase(): Database
{
    $db = new Database(new DatabaseConfig(
        driver: 'sqlite',
        database: ':memory:'
    ));

    $db->execute(
        'create table test_users (
            id integer primary key autoincrement,
            email text not null,
            role text not null,
            meta text,
            created_at text,
            profile text
        )'
    );

    return $db;
}

function seedModelRows(Database $db): void
{
    $db->table('test_users')->insert([
        'email' => 'first@example.com',
        'role' => 'user',
        'meta' => json_encode(['tags' => ['api', 'db']], JSON_THROW_ON_ERROR),
        'created_at' => '2026-06-17T10:00:00+00:00',
        'profile' => json_encode(['name' => 'Ada'], JSON_THROW_ON_ERROR),
    ]);

    $db->table('test_users')->insert([
        'email' => 'second@example.com',
        'role' => 'admin',
        'meta' => json_encode(['tags' => ['ops']], JSON_THROW_ON_ERROR),
        'created_at' => '2026-06-17T11:00:00+00:00',
        'profile' => json_encode(['name' => 'Linus'], JSON_THROW_ON_ERROR),
    ]);
}
