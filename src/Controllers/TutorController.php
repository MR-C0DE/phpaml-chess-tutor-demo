<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\{ChessContext, Lesson};
use App\Services\DeepSeekTutor;
use PHPAML\Http\{Request, Response};
use PHPAML\Mvc\Controller;
use PHPAML\Session\Session;
use Throwable;

final class TutorController extends Controller
{
    public function __construct(private readonly Session $session) {}

    public function lessons(Request $request): Response
    {
        $userId = $this->userId(); if ($userId === null) return $this->json(['error' => 'Authentication required.'], 401);
        try {
            $items = ChessContext::connect()->lessons()->where('userId', '=', $userId)->orderBy('updatedAt', 'desc')->limit(20)->all();
            return $this->json(['lessons' => array_map(static fn (Lesson $item): array => self::summary($item), $items)]);
        } catch (Throwable) { return $this->json(['error' => 'Lessons are temporarily unavailable.'], 503); }
    }

    public function start(Request $request): Response
    {
        $userId = $this->userId(); if ($userId === null) return $this->json(['error' => 'Authentication required.'], 401);
        try {
            $lesson = new Lesson(); $lesson->userId = $userId; $lesson->title = 'Training game · ' . gmdate('M j, H:i');
            $lesson->status = 'active'; $lesson->position = (string) $request->input('position', 'start');
            $lesson->moves = []; $lesson->score = 0; $lesson->createdAt = $lesson->updatedAt = gmdate(DATE_ATOM);
            ChessContext::connect()->lessons()->add($lesson);
            return $this->json(['lesson' => self::summary($lesson)], 201);
        } catch (Throwable) { return $this->json(['error' => 'Unable to start a lesson.'], 503); }
    }

    public function move(Request $request): Response
    {
        $userId = $this->userId(); if ($userId === null) return $this->json(['error' => 'Authentication required.'], 401);
        $lessonId = trim((string) $request->input('lessonId'));
        $move = trim((string) $request->input('move'));
        $position = mb_substr((string) $request->input('position'), 0, 2000);
        $locale = $request->input('locale') === 'fr' ? 'fr' : 'en';
        $legalReplies = array_values(array_filter((array) $request->input('legalReplies', []), static fn ($v): bool => is_string($v) && preg_match('/^[a-h][1-8][a-h][1-8][qrbn]?$/', $v) === 1));
        if ($lessonId === '' || preg_match('/^[a-h][1-8][a-h][1-8][qrbn]?$/', $move) !== 1 || $legalReplies === []) return $this->json(['error' => 'Invalid chess move payload.'], 422);
        try {
            $db = ChessContext::connect(); $lesson = $db->lessons()->find($lessonId);
            if (!$lesson || $lesson->userId !== $userId) return $this->json(['error' => 'Lesson not found.'], 404);
            $review = (new DeepSeekTutor())->review($position, $move, $legalReplies, $locale);
            $lesson->moves[] = ['ply' => count($lesson->moves) + 1, 'move' => $move, 'reply' => $review['reply'], 'quality' => $review['quality'], 'score' => $review['score'], 'explanation' => $review['explanation'], 'tip' => $review['tip'], 'createdAt' => gmdate(DATE_ATOM)];
            $lesson->position = $position; $lesson->score = (int) round(array_sum(array_column($lesson->moves, 'score')) / count($lesson->moves)); $lesson->updatedAt = gmdate(DATE_ATOM);
            $db->lessons()->update($lesson);
            return $this->json(['review' => $review, 'lesson' => self::summary($lesson)]);
        } catch (Throwable $error) { return $this->json(['error' => str_contains($error->getMessage(), 'Tutor AI') ? $error->getMessage() : 'Tutor could not analyze this move.'], 503); }
    }

    private function userId(): ?string { $value = $this->session->get('user_id'); return is_string($value) ? $value : null; }
    /** @return array<string, mixed> */
    private static function summary(Lesson $lesson): array { return ['id' => $lesson->id, 'title' => $lesson->title, 'status' => $lesson->status, 'score' => $lesson->score, 'moves' => $lesson->moves, 'updatedAt' => $lesson->updatedAt]; }
}
