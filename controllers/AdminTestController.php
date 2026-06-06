<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Logger;
use App\Models\Test;
use App\Models\User;
use App\Helpers\JwtHelper;

class AdminTestController extends Controller
{
    private Test      $testModel;
    private Logger    $logger;
    private JwtHelper $jwt;
    private User      $userModel;

    private const STORAGE_PATH = __DIR__ . '/../storage/tests';

    public function __construct()
    {
        $this->testModel = new Test();
        $this->userModel = new User();
        $this->logger    = Logger::getInstance();
        $this->jwt       = new JwtHelper();
    }

    // GET /api/admin/tests
    public function index(array $params = []): void
    {
        $this->requireAdmin();

        $search     = isset($_GET['search'])     ? trim($_GET['search'])     : null;
        $category   = isset($_GET['category'])   ? trim($_GET['category'])   : null;
        $difficulty = isset($_GET['difficulty']) ? trim($_GET['difficulty']) : null;
        $status     = isset($_GET['status'])     ? trim($_GET['status'])     : null;
        $sortBy     = $_GET['sortBy']     ?? 'created_at';
        $sortOrder  = strtoupper($_GET['sortOrder'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        $page       = max(1, (int)($_GET['page']  ?? 1));
        $limit      = max(1, min(100, (int)($_GET['limit'] ?? 12)));
        $offset     = ($page - 1) * $limit;

        $allowedSort = ['title', 'created_at', 'updated_at', 'difficulty', 'max_score'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'created_at';
        }

        // Build WHERE
        $where  = [];
        $binds  = [];

        if ($search !== null && $search !== '') {
            $where[]  = '(title LIKE ? OR description LIKE ?)';
            $binds[]  = "%{$search}%";
            $binds[]  = "%{$search}%";
        }
        if ($category !== null && $category !== '') {
            $where[]  = 'category = ?';
            $binds[]  = $category;
        }
        if ($difficulty !== null && $difficulty !== '') {
            $where[]  = 'difficulty = ?';
            $binds[]  = $difficulty;
        }
        if ($status !== null && $status !== '') {
            $where[]  = 'status = ?';
            $binds[]  = $status;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Get total
        $countSql  = "SELECT COUNT(*) FROM tests {$whereClause}";
        $countStmt = \App\Config\Database::getInstance()->prepare($countSql);
        $countStmt->execute($binds);
        $total = (int)$countStmt->fetchColumn();

        // Get page
        $db   = \App\Config\Database::getInstance();
        $sql  = "SELECT id, title, description, category, difficulty, max_score, json_path AS filename,
                        status, time_limit, created_at, updated_at
                 FROM tests {$whereClause}
                 ORDER BY {$sortBy} {$sortOrder}
                 LIMIT {$limit} OFFSET {$offset}";
        $stmt = $db->prepare($sql);
        $stmt->execute($binds);
        $tests = $stmt->fetchAll() ?: [];

        $this->json([
            'tests'      => $tests,
            'total'      => $total,
            'page'       => $page,
            'totalPages' => (int)ceil($total / $limit),
        ]);
    }

    // GET /api/admin/tests/{id}
    public function show(array $params = []): void
    {
        $this->requireAdmin();

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            $this->error('Invalid test ID', 400);
        }

        $test = $this->testModel->findById($id);
        if (!$test) {
            $this->error('Test not found', 404);
        }

        // Expose json_path as filename for frontend
        $test['filename'] = $test['json_path'];
        unset($test['json_path']);

        $this->json($test);
    }

    // POST /api/admin/tests
    public function create(array $params = []): void
    {
        $this->requireAdmin();

        $title      = trim($_POST['title']       ?? '');
        $description = trim($_POST['description'] ?? '');
        $category   = trim($_POST['category']    ?? '');
        $difficulty = trim($_POST['difficulty']  ?? 'medium');
        $maxScore   = (int)($_POST['max_score']  ?? 100);
        $timeLimit  = isset($_POST['time_limit']) && $_POST['time_limit'] !== ''
                        ? (int)$_POST['time_limit']
                        : null;
        $status     = trim($_POST['status'] ?? 'draft');

        if ($title === '') {
            $this->error('Title is required', 422);
        }

        if (!in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
            $difficulty = 'medium';
        }

        if (!in_array($status, ['active', 'draft', 'archived'], true)) {
            $status = 'draft';
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->error('JSON file is required', 422);
        }

        $filename = $this->uploadJson($_FILES['file']);

        $id   = $this->testModel->create([
            'title'       => $title,
            'description' => $description ?: null,
            'category'    => $category ?: null,
            'difficulty'  => $difficulty,
            'max_score'   => $maxScore,
            'json_path'   => $filename,
            'status'      => $status,
            'time_limit'  => $timeLimit,
        ]);

        $test = $this->testModel->findById($id);
        $test['filename'] = $test['json_path'];
        unset($test['json_path']);

        $this->logger->info('Test created (admin)', ['test_id' => $id, 'title' => $title]);

        $this->json(['message' => 'Test created', 'test' => $test], 201);
    }

    // PUT /api/admin/tests/{id}
    public function update(array $params = []): void
    {
        $this->requireAdmin();

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            $this->error('Invalid test ID', 400);
        }

        $test = $this->testModel->findById($id);
        if (!$test) {
            $this->error('Test not found', 404);
        }

        $data = [];

        if (isset($_POST['title']) && trim($_POST['title']) !== '') {
            $data['title'] = trim($_POST['title']);
        }
        if (isset($_POST['description'])) {
            $data['description'] = trim($_POST['description']) ?: null;
        }
        if (isset($_POST['category'])) {
            $data['category'] = trim($_POST['category']) ?: null;
        }
        if (isset($_POST['difficulty']) && in_array($_POST['difficulty'], ['easy', 'medium', 'hard'], true)) {
            $data['difficulty'] = $_POST['difficulty'];
        }
        if (isset($_POST['max_score'])) {
            $data['max_score'] = (int)$_POST['max_score'];
        }
        if (isset($_POST['status']) && in_array($_POST['status'], ['active', 'draft', 'archived'], true)) {
            $data['status'] = $_POST['status'];
        }
        if (isset($_POST['time_limit'])) {
            $data['time_limit'] = $_POST['time_limit'] !== '' ? (int)$_POST['time_limit'] : null;
        }

        // Replace JSON file if a new one uploaded
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $oldFile = self::STORAGE_PATH . '/' . $test['json_path'];
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
            $data['json_path'] = $this->uploadJson($_FILES['file']);
        }

        $this->testModel->update($id, $data);

        $updated = $this->testModel->findById($id);
        $updated['filename'] = $updated['json_path'];
        unset($updated['json_path']);

        $this->logger->info('Test updated (admin)', ['test_id' => $id]);

        $this->json(['message' => 'Test updated', 'test' => $updated]);
    }

    // DELETE /api/admin/tests/{id}
    public function delete(array $params = []): void
    {
        $this->requireAdmin();

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            $this->error('Invalid test ID', 400);
        }

        $test = $this->testModel->findById($id);
        if (!$test) {
            $this->error('Test not found', 404);
        }

        // Remove physical file
        $filepath = self::STORAGE_PATH . '/' . $test['json_path'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }

        $this->testModel->delete($id);

        $this->logger->info('Test deleted (admin)', ['test_id' => $id, 'title' => $test['title']]);

        $this->json(['message' => 'Test deleted']);
    }

    // GET /api/admin/tests/{id}/download
    public function download(array $params = []): void
    {
        $this->requireAdmin();

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            $this->error('Invalid test ID', 400);
        }

        $test = $this->testModel->findById($id);
        if (!$test) {
            $this->error('Test not found', 404);
        }

        $filepath = self::STORAGE_PATH . '/' . $test['json_path'];
        if (!file_exists($filepath)) {
            $this->error('File not found on server', 404);
        }

        header_remove('Content-Type');
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . basename($test['json_path']) . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache, must-revalidate');

        readfile($filepath);
        exit;
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /** Ensure the current request carries a valid admin JWT (role checked from DB, same as AdminUserController) */
    private function requireAdmin(): void
    {
        $token   = $this->jwt->fromHeader();
        $payload = $token ? $this->jwt->verify($token) : null;

        if (!$payload) {
            $this->error('Unauthorized', 401);
        }

        $user = $this->userModel->findById((int)$payload['sub']);
        if (!$user || ($user['role'] ?? 'user') !== 'admin') {
            $this->error('Forbidden', 403);
        }
    }

    /** Upload a JSON file into storage/tests/ and return the generated filename */
    private function uploadJson(array $file): string
    {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'json') {
            $this->error('Only .json files are allowed', 422);
        }

        if ($file['size'] > 10 * 1024 * 1024) {
            $this->error('File too large (max 10 MB)', 422);
        }

        // Validate JSON content
        $content = file_get_contents($file['tmp_name']);
        json_decode($content);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON file: ' . json_last_error_msg(), 422);
        }

        $uniqueName = uniqid('test_', true) . '.json';

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
