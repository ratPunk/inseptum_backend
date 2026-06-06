<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Logger;
use App\Models\Test;
use App\Models\UserTest;
use App\Helpers\JwtHelper;

class TestController extends Controller
{
    private Test $testModel;
    private UserTest $userTestModel;
    private JwtHelper $jwt;
    private Logger $logger;

    public function __construct()
    {
        $this->testModel = new Test();
        $this->userTestModel = new UserTest();
        $this->jwt = new JwtHelper();
        $this->logger = Logger::getInstance();
    }

    // GET /api/tests
    public function index(array $params = []): void
    {
        $category = isset($_GET['category']) ? trim($_GET['category']) : null;
        $difficulty = isset($_GET['difficulty']) ? trim($_GET['difficulty']) : null;
        $tests = $this->testModel->findAll($category, $difficulty);
        $userId = $this->getAuthUserId();
        if ($userId) {
            $userTests = $this->userTestModel->findByUser($userId);
            $progressMap = [];
            foreach ($userTests as $ut) {
                $testId = (int)$ut['test_id'];
                if (!isset($progressMap[$testId])
                    || $ut['percentage'] > $progressMap[$testId]['percentage']) {
                    $progressMap[$testId] = $ut;
                }
            }
            foreach ($tests as &$test) {
                $tid = (int)$test['id'];
                if (isset($progressMap[$tid])) {
                    $test['user_progress'] = [
                        'status' => $progressMap[$tid]['status'],
                        'score' => (int)$progressMap[$tid]['score'],
                        'max_score' => (int)$progressMap[$tid]['max_score'],
                        'percentage' => (float)$progressMap[$tid]['percentage'],
                        'attempts' => (int)$progressMap[$tid]['attempt'],
                    ];
                } else {
                    $test['user_progress'] = null;
                }
            }
            unset($test);
        }
        $this->json(['success' => true, 'data' => $tests]);
    }

    // GET /api/tests/{id}
    public function show(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            $this->error('Invalid test ID', 400);
        }
        $test = $this->testModel->findById($id);
        if (!$test) {
            $this->error('Test not found', 404);
        }
        unset($test['json_path']);
        $this->json(['success' => true, 'data' => $test]);
    }

    // GET /api/tests/{id}/content
    public function content(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            $this->error('Invalid test ID', 400);
        }
        $content = $this->testModel->getContent($id);
        if (!$content) {
            $this->error('Test content not found', 404);
        }
        $this->json(['success' => true, 'data' => $content]);
    }

    // POST /api/tests/{id}/start
    public function start(array $params = []): void
    {
        $userId = $this->requireAuth();
        $testId = (int)($params['id'] ?? 0);
        if ($testId <= 0) {
            $this->error('Invalid test ID', 400);
        }
        $test = $this->testModel->findById($testId);
        if (!$test) {
            $this->error('Test not found', 404);
        }
        $maxScore = (int)$test['max_score'];
        $attemptId = $this->userTestModel->startAttempt($userId, $testId, $maxScore);
        $this->json(['message' => 'Test attempt started', 'attempt_id' => $attemptId], 201);
    }

    // POST /api/tests/{id}/submit
    public function submit(array $params = []): void
    {
        $userId = $this->requireAuth();
        $testId = (int)($params['id'] ?? 0);
        if ($testId <= 0) {
            $this->error('Invalid test ID', 400);
        }
        $body = $this->getBody();
        if (!isset($body['answers']) || !is_array($body['answers'])) {
            $this->error('Answers are required', 422);
        }
        $content = $this->testModel->getContent($testId);
        if (!$content) {
            $this->error('Test content not found', 404);
        }
        $score = 0;
        $maxScore = 0;
        $results = [];
        foreach ($content['questions'] as $question) {
            $qId = $question['id'];
            $points = (int)($question['points'] ?? 1);
            $maxScore += $points;
            $userAnswer = $body['answers'][$qId] ?? null;
            $isCorrect = $userAnswer === $question['correctAnswerId'];
            if ($isCorrect) {
                $score += $points;
            }
            $results[] = [
                'question_id' => $qId,
                'user_answer' => $userAnswer,
                'correct_answer' => $question['correctAnswerId'],
                'is_correct' => $isCorrect,
                'points' => $isCorrect ? $points : 0,
            ];
        }
        $latestAttempt = $this->userTestModel->findLatest($userId, $testId);
        if ($latestAttempt && $latestAttempt['status'] === 'in_progress') {
            $attemptId = (int)$latestAttempt['id'];
        } else {
            $attemptId = $this->userTestModel->startAttempt($userId, $testId, $maxScore);
        }
        $this->userTestModel->completeAttempt($attemptId, $score, $maxScore, $body['answers']);
        $percentage = $maxScore > 0 ? round(($score / $maxScore) * 100, 2) : 0;
        $this->json([
            'score' => $score,
            'max_score' => $maxScore,
            'percentage' => $percentage,
            'results' => $results,
            'attempt_id' => $attemptId,
        ]);
    }

    // GET /api/tests/{id}/progress
    public function progress(array $params = []): void
    {
        $userId = $this->requireAuth();
        $testId = (int)($params['id'] ?? 0);
        if ($testId <= 0) {
            $this->error('Invalid test ID', 400);
        }
        $attempts = $this->userTestModel->findAllAttempts($userId, $testId);
        $this->json(['attempts' => $attempts]);
    }

    // GET /api/user/tests-progress
    public function userProgress(array $params = []): void
    {
        $userId = $this->requireAuth();
        $progress = $this->userTestModel->getUserProgress($userId);
        $testResults = $this->userTestModel->findByUser($userId);
        $this->json(['summary' => $progress, 'results' => $testResults]);
    }

    // ─── Helpers ───────────────────────────────────────

    private function getAuthUserId(): ?int
    {
        $token = $this->jwt->fromHeader();
        if (!$token) { return null; }
        $payload = $this->jwt->verify($token);
        return $payload ? (int)($payload['sub'] ?? 0) : null;
    }

    private function requireAuth(): int
    {
        $userId = $this->getAuthUserId();
        if (!$userId) {
            $this->error('Unauthorized', 401);
        }
        return $userId;
    }
}
