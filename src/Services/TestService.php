<?php
declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AppException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Test;
use App\Repositories\TestPassedRepository;
use App\Repositories\TestRepository;
use App\Validators\TestValidator;

class TestService
{
    private TestRepository $repo;
    private TestPassedRepository $passedRepo;
    private TestValidator $validator;
    private string $uploadDir;

    public function __construct(
        TestRepository $repo,
        TestPassedRepository $passedRepo,
        TestValidator $validator
    ) {
        $this->repo       = $repo;
        $this->passedRepo = $passedRepo;
        $this->validator  = $validator;
        $this->uploadDir  = dirname(__DIR__, 2) . '/testsFolder/';
    }

    /**
     * Получить вопросы теста (без правильных ответов).
     * Если передан $questionId — вернуть только один вопрос.
     *
     * @return array|object|null
     */
    public function getTestFile(int $testId, ?int $questionId = null)
    {
        $row = $this->repo->rawById($testId);
        if ($row === null) {
            throw new NotFoundException('Тест по id не найден');
        }

        $filePath = (string)($row['file_path'] ?? '');
        if ($filePath === '') {
            throw new NotFoundException('Файл теста не найден');
        }

        $fullPath = $this->uploadDir . $filePath . '.json';
        if (!is_file($fullPath)) {
            throw new NotFoundException('Файл теста не найден');
        }

        $contents = @file_get_contents($fullPath);
        $test = $contents !== false ? json_decode($contents) : null;
        if ($test === null) {
            throw new NotFoundException('Файл теста не найден');
        }

        if (is_array($test)) {
            foreach ($test as $question) {
                unset($question->correctAnswer);
            }
        }

        if ($questionId !== null && is_array($test)) {
            foreach ($test as $question) {
                if (isset($question->id) && (int)$question->id === $questionId) {
                    return $question;
                }
            }
            return null;
        }

        return $test;
    }

    /**
     * Подсчитать количество правильных ответов пользователя.
     *
     * @param array $userAnswers   Массив пар [{questionId, answer}, ...]
     */
    public function getCorrectAnswersCount(int $testId, array $userAnswers): int
    {
        $row = $this->repo->rawById($testId);
        if ($row === null) {
            throw new NotFoundException('Тест по id не найден');
        }

        $filePath = (string)($row['file_path'] ?? '');
        $fullPath = $this->uploadDir . $filePath . '.json';
        if ($filePath === '' || !is_file($fullPath)) {
            throw new NotFoundException('Файл теста не найден');
        }

        $contents = @file_get_contents($fullPath);
        $test = $contents !== false ? json_decode($contents) : null;
        if ($test === null) {
            throw new NotFoundException('Файл теста не найден');
        }

        $userMap    = array_column($userAnswers, 'answer', 'questionId');
        $correctMap = array_column($test, 'correctAnswer', 'id');

        $count = 0;
        foreach ($correctMap as $id => $correct) {
            if (isset($userMap[$id]) && $userMap[$id] == $correct) {
                $count++;
            }
        }
        return $count;
    }

    public function markPassed(int $userId, int $testId): bool
    {
        if ($userId <= 0 || $testId <= 0) {
            throw new ValidationException('Не указаны user_id и test_id');
        }
        $row = $this->passedRepo->markPassed($userId, $testId);
        return (int)($row['is_passed'] ?? 0) === 1;
    }

    public function isPassed(int $userId, int $testId): bool
    {
        if ($userId <= 0 || $testId <= 0) {
            throw new ValidationException('Не указаны user_id и test_id');
        }
        $row = $this->passedRepo->findOne($userId, $testId);
        return $row !== null && (int)$row['is_passed'] === 1;
    }

    public function listAll(): array
    {
        $tests = $this->repo->findAll();
        if (empty($tests)) {
            throw new NotFoundException('Тесты не найдены');
        }
        $data = array_map(static fn(Test $t) => $t->toArray(), $tests);
        return ['data' => $data, 'count' => count($data)];
    }

    public function getOne(int $id): array
    {
        $test = $this->repo->findOne($id);
        if ($test === null) {
            throw new NotFoundException('Тест не найден');
        }
        return $test->toArray();
    }

    public function create(array $data, ?array $file): array
    {
        $clean = $this->validator->validate($data, $file, true);

        $fileContent = file_get_contents($file['tmp_name']);
        $questions = json_decode($fileContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ValidationException('Ошибка валидации JSON: ' . json_last_error_msg());
        }
        $questionCount = is_array($questions) ? count($questions) : 0;

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }

        $fileNameWithExt = time() . '_' . basename((string)$file['name']);
        $targetPath = $this->uploadDir . $fileNameWithExt;
        if (!@move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new AppException('Не удалось сохранить файл в testsFolder', 500);
        }
        $fileNameOnly = pathinfo($fileNameWithExt, PATHINFO_FILENAME);

        try {
            $newId = $this->repo->create(
                $clean['title'],
                $clean['description'],
                $clean['time_limit'],
                $questionCount,
                $fileNameOnly
            );
        } catch (\Throwable $e) {
            @unlink($targetPath);
            throw $e;
        }

        if ($clean['topic_id'] > 0) {
            $this->repo->attachToTopic($newId, $clean['topic_id']);
        }

        return [
            'id'             => $newId,
            'title'          => $clean['title'],
            'question_count' => $questionCount,
            'file_path'      => $fileNameOnly,
        ];
    }

    public function update(int $id, array $data, ?array $file): array
    {
        $current = $this->repo->rawById($id);
        if ($current === null) {
            throw new NotFoundException('Тест не найден');
        }

        $clean = $this->validator->validate($data, $file, false);

        $fields = [
            'title'       => $clean['title'],
            'description' => $clean['description'],
            'time_limit'  => $clean['time_limit'],
        ];

        if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && (int)($file['size'] ?? 0) > 0) {
            $fileContent = file_get_contents($file['tmp_name']);
            $questions = json_decode($fileContent, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $questionCount = is_array($questions) ? count($questions) : 0;

                if (!is_dir($this->uploadDir)) {
                    mkdir($this->uploadDir, 0777, true);
                }

                $fileNameWithExt = time() . '_' . basename((string)$file['name']);
                $targetPath = $this->uploadDir . $fileNameWithExt;
                if (@move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $fileNameOnly = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
                    $fields['file_path']      = $fileNameOnly;
                    $fields['question_count'] = $questionCount;

                    if (!empty($current['file_path'])) {
                        $oldPath = $this->uploadDir . $current['file_path'] . '.json';
                        if (file_exists($oldPath)) {
                            @unlink($oldPath);
                        }
                    }
                }
            }
        }

        $this->repo->update($id, $fields);
        $row = $this->repo->rawById($id);
        return $row ?? [];
    }

    public function delete(int $id): array
    {
        $current = $this->repo->rawById($id);
        if ($current === null) {
            throw new NotFoundException('Тест не найден');
        }

        $this->repo->detachFromArticles($id);

        if (!empty($current['file_path'])) {
            $oldPath = $this->uploadDir . $current['file_path'] . '.json';
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }

        $this->repo->delete($id);

        return ['id' => (int)$current['id'], 'title' => (string)$current['title']];
    }
}
