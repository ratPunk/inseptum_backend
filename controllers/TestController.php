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
            $attemptsCount = [];
            foreach ($userTests as $ut) {
                $testId = (int)$ut['test_id'];
                $attemptsCount[$testId] = ($attemptsCount[$testId] ?? 0) + 1;

                // Лучшая попытка: сначала passed=1, затем по проценту.
                $current = $progressMap[$testId] ?? null;
                $isBetter = $current === null
                    || ((int)$ut['passed'] > (int)$current['passed'])
                    || ((int)$ut['passed'] === (int)$current['passed']
                        && (float)$ut['percentage'] > (float)$current['percentage']);
                if ($isBetter) {
                    $progressMap[$testId] = $ut;
                }
            }
            foreach ($tests as &$test) {
                $tid = (int)$test['id'];
                if (isset($progressMap[$tid])) {
                    $test['user_progress'] = [
                        'status' => $progressMap[$tid]['status'],
                        'passed' => (bool)(int)$progressMap[$tid]['passed'],
                        'score' => (int)$progressMap[$tid]['score'],
                        'max_score' => (int)$progressMap[$tid]['max_score'],
                        'percentage' => (float)$progressMap[$tid]['percentage'],
                        'attempts' => (int)($attemptsCount[$tid] ?? 0),
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

        // Нормализуем ответы: поддерживаем два формата:
        //   1. Массив объектов: [{"question_id": 1, "answer": "a"}, ...]  (формат фронтенда)
        //   2. Словарь:         {"1": "a", "2": "b"}                      (legacy)
        $answersMap = [];
        $rawAnswers = $body['answers'];
        if (isset($rawAnswers[0]) && is_array($rawAnswers[0])) {
            // Формат массива объектов
            foreach ($rawAnswers as $item) {
                if (isset($item['question_id'])) {
                    $answersMap[(int)$item['question_id']] = $item['answer'] ?? null;
                }
            }
        } else {
            // Формат словаря
            foreach ($rawAnswers as $qId => $answer) {
                $answersMap[(int)$qId] = $answer;
            }
        }

        $score = 0;
        $maxScore = 0;
        $correctAnswers = 0;
        $results = [];
        foreach ($content['questions'] as $question) {
            $qId = (int)$question['id'];
            $points = (int)($question['points'] ?? 1);
            $maxScore += $points;
            // Поддерживаем оба имени поля: correct_answer (текущий JSON) и correctAnswerId (legacy)
            $correctAnswer = $question['correct_answer'] ?? $question['correctAnswerId'] ?? null;
            $userAnswer = $answersMap[$qId] ?? null;
            $isCorrect = $userAnswer !== null && $userAnswer === $correctAnswer;
            if ($isCorrect) {
                $score += $points;
                $correctAnswers++;
            }
            $results[] = [
                'question_id' => $qId,
                'user_answer' => $userAnswer,
                'correct_answer' => $correctAnswer,
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
        $percentage = $maxScore > 0 ? round(($score / $maxScore) * 100, 2) : 0.0;
        $totalQuestions = count($content['questions']);
        $passingScore = (float)($content['passing_score'] ?? 60);
        $passed = $maxScore > 0 && ($percentage >= $passingScore);
        $this->userTestModel->completeAttempt($attemptId, $score, $maxScore, $answersMap, (bool)$passed);
        $this->json([
            'success' => true,
            'data' => [
                'attempt_id' => $attemptId,
                'test_id' => $testId,
                'score' => $score,
                'max_score' => $maxScore,
                'percentage' => $percentage,
                'passed' => $passed,
                'correct_answers' => $correctAnswers,
                'total_questions' => $totalQuestions,
                'results' => $results,
                'created_at' => date('c'),
            ],
        ]);
    }

    // GET /api/tests/results  — список результатов текущего пользователя
    public function myResults(array $params = []): void
    {
        $userId = $this->requireAuth();
        $rows = $this->userTestModel->findByUser($userId);

        $data = array_map(static function (array $row): array {
            return [
                'test_id'    => (int)$row['test_id'],
                'passed'     => (bool)(int)$row['passed'],
                'score'      => (int)$row['score'],
                'max_score'  => (int)$row['max_score'],
                'percentage' => (float)$row['percentage'],
                'status'     => $row['status'],
                'created_at' => $row['created_at'],
            ];
        }, $rows);

        $this->json(['success' => true, 'data' => $data]);
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
