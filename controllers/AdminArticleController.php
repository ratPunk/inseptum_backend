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

    // Absolute path resolved at class-load time so it is correct on any OS / deploy layout.
    // __DIR__ here is the `controllers/` directory; we go one level up to project root.
    private const STORAGE_PATH = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'articles';

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

    // GET /api/admin/articles/{id}/download
    public function download(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            $this->error('Invalid article ID', 400);
        }

        $article = $this->articleModel->findById($id);
        if (!$article) {
            $this->error('Article not found', 404);
        }

        $filepath = self::STORAGE_PATH . '/' . $article['filename'];
        if (!file_exists($filepath)) {
            $this->error('File not found on server', 404);
        }

        // Remove default Content-Type header set in index.php
        header_remove('Content-Type');

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $article['filename'] . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache, must-revalidate');

        readfile($filepath);
        exit;
    }

    // ─── Private helpers ──────────────────────────────────────────────────

    private function uploadDocx(array $file): string
    {
        // ── 1. Validate extension ─────────────────────────────────────────
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if ($ext !== 'docx') {
            $this->error('Only .docx files are allowed', 422);
        }

        // ── 2. Resolve storage path to a real absolute path ───────────────
        // realpath() returns false if the path does not exist yet, so we
        // resolve the parent (project root) and append manually.
        $rawPath     = self::STORAGE_PATH;
        $projectRoot = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..') ?: (__DIR__ . DIRECTORY_SEPARATOR . '..');
        $storagePath = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'articles';

        // ── 3. Create directory if absent ─────────────────────────────────
        $dirExists = is_dir($storagePath);
        if (!$dirExists) {
            error_clear_last();
            $mkdirOk   = mkdir($storagePath, 0755, true);
            $mkdirErr  = error_get_last();
            $dirExists = $mkdirOk;

            if (!$mkdirOk) {
                $this->logger->error('mkdir failed for storage path', [
                    'path'      => $storagePath,
                    'php_error' => $mkdirErr,
                ]);
                $this->error('Failed to create upload directory', 500);
            }
        }
        $dirWritable = is_writable($storagePath);

        // ── 4. Build destination ──────────────────────────────────────────
        $uniqueName  = uniqid('article_', true) . '.docx';
        $destination = $storagePath . DIRECTORY_SEPARATOR . $uniqueName;

        // ── 5. Inspect tmp file ───────────────────────────────────────────
        $tmpName     = $file['tmp_name'] ?? '';
        $tmpExists   = $tmpName !== '' && file_exists($tmpName);
        $tmpReadable = $tmpExists && is_readable($tmpName);
        $isUploaded  = $tmpExists && is_uploaded_file($tmpName);
        $tmpSize     = $tmpExists ? filesize($tmpName) : null;

        // ── 6. Log full diagnostics ───────────────────────────────────────
        $this->logger->article('uploadDocx: attempt', [
            'original_name'          => $file['name']  ?? '',
            'upload_error_code'      => $file['error'] ?? 'n/a',
            'tmp_name'               => $tmpName,
            'tmp_exists'             => $tmpExists,
            'tmp_readable'           => $tmpReadable,
            'is_uploaded_file'       => $isUploaded,
            'tmp_size_bytes'         => $tmpSize,
            'raw_STORAGE_PATH_const' => $rawPath,
            'resolved_storage_path'  => $storagePath,
            'storage_dir_exists'     => $dirExists,
            'storage_dir_writable'   => $dirWritable,
            'destination'            => $destination,
            'php_upload_tmp_dir'     => ini_get('upload_tmp_dir') ?: sys_get_temp_dir(),
            'upload_max_filesize'    => ini_get('upload_max_filesize'),
            'post_max_size'          => ini_get('post_max_size'),
            'open_basedir'           => ini_get('open_basedir') ?: '(none)',
            'process_user'           => function_exists('posix_getpwuid')
                                            ? (posix_getpwuid(posix_geteuid())['name'] ?? 'unknown')
                                            : 'n/a (Windows)',
            'php_version'            => PHP_VERSION,
            'os'                     => PHP_OS,
        ]);

        if (!$tmpExists || !$isUploaded) {
            $this->logger->error('uploadDocx: tmp file invalid or not a real upload', [
                'tmp_name'        => $tmpName,
                'tmp_exists'      => $tmpExists,
                'is_uploaded_file'=> $isUploaded,
            ]);
            $this->error('Uploaded file is missing or corrupted', 422);
        }

        if (!$dirWritable) {
            $this->logger->error('uploadDocx: storage directory not writable', [
                'storage_path'    => $storagePath,
                'dir_exists'      => $dirExists,
            ]);
            $this->error('Server storage directory is not writable', 500);
        }

        // ── 7. Try move_uploaded_file ─────────────────────────────────────
        error_clear_last();
        $moved    = move_uploaded_file($tmpName, $destination);
        $moveErr  = error_get_last();

        if ($moved) {
            $this->logger->article('uploadDocx: move_uploaded_file succeeded', [
                'destination' => $destination,
                'filename'    => $uniqueName,
            ]);
            return $uniqueName;
        }

        $this->logger->error('uploadDocx: move_uploaded_file failed', [
            'tmp_name'    => $tmpName,
            'destination' => $destination,
            'php_error'   => $moveErr,
        ]);

        // ── 8. Fallback: copy() + unlink() (works cross-drive on Windows) ─
        error_clear_last();
        $copied   = copy($tmpName, $destination);
        $copyErr  = error_get_last();

        if ($copied) {
            @unlink($tmpName);
            $this->logger->article('uploadDocx: fallback copy() succeeded', [
                'destination' => $destination,
                'filename'    => $uniqueName,
            ]);
            return $uniqueName;
        }

        // ── 9. Both strategies failed — log everything and return 500 ─────
        $this->logger->error('uploadDocx: copy() fallback also failed', [
            'tmp_name'              => $tmpName,
            'destination'           => $destination,
            'destination_dir'       => dirname($destination),
            'destination_dir_exists'=> is_dir(dirname($destination)),
            'destination_writable'  => is_writable(dirname($destination)),
            'copy_php_error'        => $copyErr,
            'tmp_size_bytes'        => $tmpSize,
            'free_disk_bytes'       => function_exists('disk_free_space') ? disk_free_space($storagePath) : 'n/a',
        ]);

        $this->error('Failed to save uploaded file', 500);
    }
}