<?php

declare(strict_types=1);

namespace App\Models;

use AML\Data\Entity;
use AML\Data\MongoDB\Metadata\{Collection, DocumentId};

#[Collection('lessons')]
final class Lesson extends Entity
{
    #[DocumentId]
    public string $id;
    public string $userId;
    public string $title;
    public string $status;
    public string $position;
    /** @var list<array<string, mixed>> */
    public array $moves = [];
    public int $score = 0;
    public string $createdAt;
    public string $updatedAt;
}
