<?php
declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AppException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Article;
use App\Repositories\ArticleReadRepository;
use App\Repositories\ArticleRepository;
use App\Support\DocxConverter;
use App\Validators\ArticleValidator;

class ArticleService
{
    private ArticleRepository $repo;
    private ArticleReadRepository $readRepo;
    private ArticleValidator $validator;
    private DocxConverter $converter;
    private string $uploadDir;

    public function __construct(
        ArticleRepository $repo,
        ArticleReadRepository $readRepo,
        ArticleValidator $validator,
        DocxConverter $converter
    ) {
        $this->repo      = $repo;
        $this->readRepo  = $readRepo;
        $this->validator = $validator;
        $this->converter = $converter;
        // Project root is two levels up from src/Services
        $this->uploadDir = dirname(__DIR__, 2) . '/articlesFolder/';
    }

    /**
     * Получить HTML-представление статьи из её .docx файла.
     */
    public function getArticleHtml(int $articleId): string
    {
        $article = $this->repo->rawById($articleId);
        if ($article === null) {
            throw new NotFoundException('Статья не найдена');
        }

        $filePath = (string)($article['file_path'] ?? '');
        if ($filePath === '') {
            throw new NotFoundException('Файл статьи не найден');
        }

        $html = $this->converter->convert($filePath);
        if ($html === '') {
            throw new NotFoundException('Содержимое статьи не найдено');
        }

        return $html;
    }

    /**
     * Получить (а при отсутствии — создать) запись о прочтении статьи пользователем.
     *
     * @return array{data: array, created: bool}
     */
    public function getReadStatus(int $articleId, int $userId): array
    {
        if ($articleId <= 0 || $userId <= 0) {
            throw new ValidationException('Некорректный ID статьи или пользователя');
        }
        $row = $this->readRepo->findByUserAndArticle($userId, $articleId);
        if ($row !== null) {
            return ['data' => ArticleReadRepository::format($row), 'created' => false];
        }

        $this->readRepo->createUnread($userId, $articleId);
        $row = $this->readRepo->findByUserAndArticle($userId, $articleId);
        if ($row === null) {
            throw new AppException('Запись создана, но не удалось её прочитать', 500);
        }
        return ['data' => ArticleReadRepository::format($row), 'created' => true];
    }

    /**
     * Отметить статью как прочитанную.
     */
    public function markAsRead(int $articleId, int $userId): array
    {
        if ($articleId <= 0 || $userId <= 0) {
            throw new ValidationException('Некорректный ID статьи или пользователя');
        }
        $this->readRepo->markRead($userId, $articleId);
        $row = $this->readRepo->findByUserAndArticle($userId, $articleId);
        if ($row === null) {
            throw new AppException('Запись обновлена, но не удалось её прочитать', 500);
        }
        return ArticleReadRepository::format($row);
    }

    public function listAll(): array
    {
        $articles = $this->repo->findAll();
        if (empty($articles)) {
            throw new NotFoundException('Статьи не найдены');
        }
        $data = array_map(static fn(Article $a) => $a->toArray(), $articles);
        return ['data' => $data, 'count' => count($data)];
    }

    public function listByTopic(int $topicId): array
    {
        $articles = $this->repo->findByTopicId($topicId);
        if (empty($articles)) {
            throw new NotFoundException('Статьи не найдены');
        }
        $data = array_map(static fn(Article $a) => $a->toArray(), $articles);
        return ['data' => $data, 'count' => count($data)];
    }

    public function getOne(int $id): array
    {
        $article = $this->repo->findOne($id);
        if ($article === null) {
            throw new NotFoundException('Статья не найдена');
        }
        return $article->toArray();
    }

    public function create(array $data, ?array $file): array
    {
        $clean = $this->validator->validate($data, $file, true);

        $originalName = basename((string)$file['name']);
        $this->ensureDocxExtension($originalName);

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }

        $target = $this->uploadDir . $originalName;
        if (file_exists($target)) {
            throw new ValidationException("Файл с названием '$originalName' уже существует. Пожалуйста, переименуйте файл.");
        }

        if (!@move_uploaded_file($file['tmp_name'], $target)) {
            throw new AppException('Не удалось сохранить файл на сервере', 500);
        }

        try {
            $newId = $this->repo->create(
                $clean['topic_id'],
                $clean['title'],
                $clean['description'],
                $originalName
            );
        } catch (\Throwable $e) {
            // Roll back the uploaded file
            @unlink($target);
            throw $e;
        }

        $article = $this->repo->findOne($newId);
        return $article ? $article->toArray() : [];
    }

    public function update(int $id, array $data, ?array $file): array
    {
        $current = $this->repo->rawById($id);
        if ($current === null) {
            throw new NotFoundException('Статья не найдена');
        }

        $clean = $this->validator->validate($data, $file, false);
        $newFileName = (string)$current['file_path'];

        if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $originalName = basename((string)$file['name']);
            $this->ensureDocxExtension($originalName);

            $target = $this->uploadDir . $originalName;
            if ($originalName !== $current['file_path'] && file_exists($target)) {
                throw new ValidationException('Файл с таким именем уже есть');
            }

            $oldFile = $this->uploadDir . $current['file_path'];
            if (file_exists($oldFile)) {
                @unlink($oldFile);
            }

            if (!@move_uploaded_file($file['tmp_name'], $target)) {
                throw new AppException('Не удалось сохранить файл на сервере', 500);
            }
            $newFileName = $originalName;
        }

        $this->repo->update(
            $id,
            $clean['topic_id'],
            $clean['title'],
            $clean['description'],
            $newFileName
        );

        $article = $this->repo->findOne($id);
        return $article ? $article->toArray() : [];
    }

    public function delete(int $id): array
    {
        $current = $this->repo->rawById($id);
        if ($current === null) {
            throw new NotFoundException('Статья не найдена');
        }

        $this->repo->delete($id);

        $message = ' Файл на сервере не найден.';
        $fileName = (string)($current['file_path'] ?? '');
        if ($fileName !== '') {
            $filePath = $this->uploadDir . $fileName;
            if (file_exists($filePath)) {
                $message = @unlink($filePath)
                    ? ' Файл также удален.'
                    : ' Запись удалена, но возникла ошибка при удалении файла.';
            }
        }

        return [
            'data'    => ['id' => (int)$current['id'], 'title' => (string)$current['title']],
            'message' => 'Статья успешно удалена.' . $message,
        ];
    }

    private function ensureDocxExtension(string $fileName): void
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($extension !== 'docx') {
            throw new ValidationException('Разрешены только файлы .docx');
        }
    }
}
