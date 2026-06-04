<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Logger;
use App\Models\Article;
use App\Models\Category;

class AdminArticleController extends Controller
{
    private Article  $articleModel;
    private Category $categoryModel;
    private Logger   $logger;

    private const STORAGE_PATH = __DIR__ . '/../storage/articles';

    public function __construct()
    {
        $this->articleModel  = new Article();
        $this->categoryModel = new Category();
        $this->logger        = Logger::getInstance();
    }

    // GET /api/admin/articles
    public function index(array $params = []): void
    {
        $options = [
            'category_id' => isset($_GET['category_id']) ? (int)$_GET['category_id'] : null,
            'search'      => $_GET['search'] ?? null,
            'sort_by'     => $_GET['sort_by'] ?? 'created_at',
            'sort_order'  => $_GET['sort_order'] ?? 'desc',
            'page'        => (int)($_GET['page'] ?? 1),
            'limit'       => (int)($_GET['limit'] ?? 12),
        ];

        // Validate category if provided
        if ($options['category_id'] !== null && !$this->categoryModel->findById($options['category_id'])) {
            $this->error('Category not found', 404);
        }

        $result = $this->articleModel->findAllPaginated($options);
        $this->json($result);
    }

    // GET /api/admin/articles/{id}
    public function show(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            $this->error('Invalid article ID', 400);
        }

        $article = $this->articleModel->findById($id);
        if (!$article) {
            $this->error('Article not found', 404);
        }

        $this->json(['article' => $article]);
    }

    // POST /api/admin/articles
    public function create(array $params = []): void
    {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $categoryId  = (int)($_POST['category_id'] ?? 0);

        if ($title === '' || $categoryId <= 0) {
            $this->error('Missing required fields: title, category_id', 422);
        }

        if (!$this->categoryModel->findById($categoryId)) {
            $this->error('Category not found', 404);
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->error('DOCX file is required', 422);
        }

        $filename = $this->uploadDocx($_FILES['file']);

        $id = $this->articleModel->create($title, $description ?: null, $filename, $categoryId);
        $article = $this->articleModel->findById($id);

        $this->logger->article('Article created (admin)', [
            'article_id' => $id,
            'title' => $title,
            'category_id' => $categoryId,
            'filename' => $filename
        ]);

        $this->json(['message' => 'Article created', 'article' => $article], 201);
    }

    // PUT /api/admin/articles/{id}
    public function update(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            $this->error('Invalid article ID', 400);
        }

        $article = $this->articleModel->findById($id);
        if (!$article) {
            $this->error('Article not found', 404);
        }

        $title       = trim($_POST['title'] ?? $article['title']);
        $description = trim($_POST['description'] ?? $article['description']);
        $categoryId  = (int)($_POST['category_id'] ?? $article['category_id']);

        if ($categoryId > 0 && !$this->categoryModel->findById($categoryId)) {
            $this->error('Category not found', 404);
        }

        $newFilename = null;
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $oldFile = self::STORAGE_PATH . '/' . $article['filename'];
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
            $newFilename = $this->uploadDocx($_FILES['file']);
        }

        $this->articleModel->update($id, $title, $description ?: null, $newFilename, $categoryId);
        $article = $this->articleModel->findById($id);

        $this->json(['message' => 'Article updated', 'article' => $article]);
    }

    // DELETE /api/admin/articles/{id}
    public function delete(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            $this->error('Invalid article ID', 400);
        }

        $article = $this->articleModel->findById($id);
        if (!$article) {
            $this->logger->article('Article delete failed (admin): not found', ['article_id' => $id]);
            $this->error('Article not found', 404);
        }

        $filepath = self::STORAGE_PATH . '/' . $article['filename'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }

        $this->articleModel->delete($id);

        $this->logger->article('Article deleted (admin)', [
            'article_id' => $id,
            'title' => $article['title']
        ]);

        $this->json(['message' => 'Article deleted']);
    }

    // GET /api/admin/categories
    public function categories(array $params = []): void
    {
        $categories = $this->categoryModel->findAll();
        $this->json(['categories' => $categories]);
    }

    // ─── Private helpers ──────────────────────────────────────────────────

    private function uploadDocx(array $file): string
    {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'docx') {
            $this->error('Only .docx files are allowed', 422);
        }

        $uniqueName = uniqid('article_', true) . '.docx';

        if (!is_dir(self::STORAGE_PATH)) {
            mkdir(self::STORAGE_PATH, 0777, true);
        }

        $destination = self::STORAGE_PATH . '/' . $uniqueName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $this->error('Failed to save uploaded file', 500);
        }

        return $uniqueName;
    }
}