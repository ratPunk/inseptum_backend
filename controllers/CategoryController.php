<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Category;

class CategoryController extends Controller
{
    private Category $categoryModel;

    public function __construct()
    {
        $this->categoryModel = new Category();
    }

    // GET /api/categories
    public function index(array $params = []): void
    {
        $categories = $this->categoryModel->findAll();

        // Add article count to each category
        $result = array_map(function ($cat) {
            $cat['article_count'] = $this->categoryModel->getArticleCount((int)$cat['id']);
            return $cat;
        }, $categories);

        $this->json(['categories' => $result]);
    }

    // GET /api/categories/{id}
    public function show(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            $this->error('Invalid category ID', 400);
        }

        $category = $this->categoryModel->findById($id);
        if (!$category) {
            $this->error('Category not found', 404);
        }

        $category['article_count'] = $this->categoryModel->getArticleCount($id);
        $this->json(['category' => $category]);
    }

    // POST /api/categories
    // Body: { "name": "...", "slug": "...", "icon": "...", "sort_order": 0 }
    public function create(array $params = []): void
    {
        $body = $this->getBody();

        $missing = $this->requireFields($body, ['name', 'slug']);
        if ($missing) {
            $this->error('Missing required fields: ' . implode(', ', $missing), 422);
        }

        $name      = trim($body['name']);
        $slug      = trim($body['slug']);
        $icon      = trim($body['icon'] ?? '');
        $sortOrder = (int)($body['sort_order'] ?? 0);

        if ($this->categoryModel->findBySlug($slug)) {
            $this->error('Category slug already exists', 409);
        }

        $id = $this->categoryModel->create($name, $slug, $icon, $sortOrder);
        $category = $this->categoryModel->findById($id);

        $this->json(['message' => 'Category created', 'category' => $category], 201);
    }

    // PUT /api/categories/{id}
    public function update(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            $this->error('Invalid category ID', 400);
        }

        $body = $this->getBody();

        $missing = $this->requireFields($body, ['name', 'slug']);
        if ($missing) {
            $this->error('Missing required fields: ' . implode(', ', $missing), 422);
        }

        $existing = $this->categoryModel->findBySlug(trim($body['slug']));
        if ($existing && (int)$existing['id'] !== $id) {
            $this->error('Category slug already taken by another category', 409);
        }

        $name      = trim($body['name']);
        $slug      = trim($body['slug']);
        $icon      = trim($body['icon'] ?? '');
        $sortOrder = (int)($body['sort_order'] ?? 0);

        $this->categoryModel->update($id, $name, $slug, $icon, $sortOrder);
        $category = $this->categoryModel->findById($id);

        $this->json(['message' => 'Category updated', 'category' => $category]);
    }

    // DELETE /api/categories/{id}
    public function delete(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            $this->error('Invalid category ID', 400);
        }

        $category = $this->categoryModel->findById($id);
        if (!$category) {
            $this->error('Category not found', 404);
        }

        $articleCount = $this->categoryModel->getArticleCount($id);
        if ($articleCount > 0) {
            $this->error("Cannot delete category with $articleCount article(s). Remove articles first.", 409);
        }

        $this->categoryModel->delete($id);
        $this->json(['message' => 'Category deleted']);
    }
}