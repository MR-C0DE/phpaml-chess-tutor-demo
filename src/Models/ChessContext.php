<?php

declare(strict_types=1);

namespace App\Models;

use AML\Data\MongoDB\{MongoConnection, MongoContext, MongoSet};
use AML\Data\MongoDB\Transport\OfficialMongoTransport;
use PHPAML\Config\Env;
use RuntimeException;

final class ChessContext extends MongoContext
{
    public static function connect(): self
    {
        $uri = trim((string) Env::get('MONGODB_URI', ''));
        if ($uri === '') {
            throw new RuntimeException('MongoDB is not configured.');
        }
        return new self(new MongoConnection(
            new OfficialMongoTransport($uri),
            (string) Env::get('MONGODB_DATABASE', 'tutorchess'),
        ));
    }

    /** @return MongoSet<User> */
    public function users(): MongoSet { return $this->set(User::class); }

    /** @return MongoSet<Lesson> */
    public function lessons(): MongoSet { return $this->set(Lesson::class); }

    /** @return array<string, mixed> */
    public function diagnostics(): array { return $this->connection->diagnostics(); }
}
