<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Logger;
use App\Models\Article;
use App\Models\Category;

class ArticleController extends Controller
{
    private Article  $articleModel;
    private Category $categoryModel;
    private Logger    $logger;

    private const STORAGE_PATH = __DIR__ . '/../storage/articles';

    // State for slugify() to ensure consistent IDs between TOC and HTML
    private array $slugifySeen = [];
    private array $slugifyCounters = [];

    public function __construct()
    {
        $this->articleModel  = new Article();
        $this->categoryModel = new Category();
        $this->logger        = Logger::getInstance();
    }

    // GET /api/articles?category_id=1
    public function index(array $params = []): void
    {
        $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;

        if ($categoryId !== null && !$this->categoryModel->findById($categoryId)) {
            $this->error('Category not found', 404);
        }

        $articles = $this->articleModel->findAll($categoryId);
        $this->json(['articles' => $articles]);
    }

    // GET /api/articles/{id}
    public function show(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            $this->error('Invalid article ID', 400);
        }

        $article = $this->articleModel->findById($id);
        if (!$article) {
            $this->logger->article('Article view failed: not found', ['article_id' => $id]);
            $this->error('Article not found', 404);
        }

        $this->logger->article('Article viewed', [
            'article_id' => $id,
            'title' => $article['title'],
            'category_id' => $article['category_id']
        ]);

        $this->json(['article' => $article]);
    }

    // GET /api/articles/{id}/content — read DOCX, render semantic HTML, generate TOC
    public function content(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            $this->error('Invalid article ID', 400);
        }

        $article = $this->articleModel->findById($id);
        if (!$article) {
            $this->logger->article('Article content read failed: not found', ['article_id' => $id]);
            $this->error('Article not found', 404);
        }

        $filepath = self::STORAGE_PATH . '/' . $article['filename'];

        if (!file_exists($filepath)) {
            $this->logger->article('Article content read failed: file missing', [
                'article_id' => $id,
                'filename' => $article['filename']
            ]);
            $this->error('DOCX file not found on server', 404);
        }

        $docxData = $this->readDocxStructured($filepath);

        // Reset slugify state before generating IDs
        $this->resetSlugifyState();

        // Generate TOC first, then HTML (both use same slugify function with consistent state)
        $toc  = $this->buildToc($docxData);
        $html = $this->renderSemanticHtml($docxData, $article['id']);

        // Log successful content read
        $this->logger->article('Article content read', [
            'article_id' => $id,
            'title' => $article['title'],
            'word_count' => $docxData['wordCount'] ?? 0,
            'toc_items' => count($toc)
        ]);

        $this->json([
            'article'   => $article,
            'html'      => $html,
            'toc'       => $toc,
            'wordCount' => $docxData['wordCount'] ?? 0,
        ]);
    }

    // GET /api/articles/{id}/toc — return only TOC (lightweight)
    public function toc(array $params = []): void
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
            $this->error('DOCX file not found on server', 404);
        }

        $docxData = $this->readDocxStructured($filepath);
        $toc      = $this->buildToc($docxData);

        $this->json(['toc' => $toc]);
    }

    // POST /api/articles — create article + upload DOCX
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

        $this->logger->article('Article created', [
            'article_id' => $id,
            'title' => $title,
            'category_id' => $categoryId,
            'filename' => $filename
        ]);

        $this->json(['message' => 'Article created', 'article' => $article], 201);
    }

    // PUT /api/articles/{id} — update article metadata + optionally replace DOCX
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

    // DELETE /api/articles/{id}
    public function delete(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            $this->error('Invalid article ID', 400);
        }

        $article = $this->articleModel->findById($id);
        if (!$article) {
            $this->logger->article('Article delete failed: not found', ['article_id' => $id]);
            $this->error('Article not found', 404);
        }

        $filepath = self::STORAGE_PATH . '/' . $article['filename'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }

        $this->articleModel->delete($id);

        $this->logger->article('Article deleted', [
            'article_id' => $id,
            'title' => $article['title']
        ]);

        $this->json(['message' => 'Article deleted']);
    }

    // Serve article images (prevents direct file access)
    public function serveImage(array $params = []): void
    {
        $articleId = (int)($params['articleId'] ?? 0);
        $filename  = basename($params['filename'] ?? '');

        if ($articleId <= 0 || $filename === '') {
            $this->error('Invalid image request', 400);
        }

        // Verify article owns this image
        $article = $this->articleModel->findById($articleId);
        if (!$article) {
            $this->error('Article not found', 404);
        }

        $filepath = self::STORAGE_PATH . '/' . $filename;

        if (!file_exists($filepath)) {
            $this->error('Image not found', 404);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $filepath);
        finfo_close($finfo);

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: public, max-age=86400');
        readfile($filepath);
        exit;
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

    /**
     * Read a DOCX file and extract structured content.
     * Returns: [paragraphs => [...], wordCount => int]
     * Each paragraph: [type => 'heading'|'paragraph'|'list'|'code'|'image', level => int, text => string, items => array]
     */
    private function readDocxStructured(string $filepath): array
    {
        $result = [
            'paragraphs' => [],
            'wordCount'   => 0,
        ];

        if (!class_exists('\ZipArchive')) {
            $content = $this->readDocxFallback($filepath);
            $result['paragraphs'][] = ['type' => 'paragraph', 'text' => $content];
            $result['wordCount']    = str_word_count($content);
            return $result;
        }

        $zip = new \ZipArchive();
        if ($zip->open($filepath) !== true) {
            $this->error('Cannot open DOCX file', 500);
        }

        $allXml = '';
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            // Only read main document body, ignore headers/footers for TOC
            if ($name === 'word/document.xml') {
                $allXml = $zip->getFromIndex($i);
                break;
            }
        }

        $zip->close();

        if ($allXml === '') {
            return $result;
        }

        $result = $this->parseDocxXml($allXml);

        return $result;
    }

    /**
     * Parse DOCX XML into structured paragraphs with heading detection.
     */
    private function parseDocxXml(string $xml): array
    {
        $dom = new \DOMDocument();
        $dom->loadXML($xml, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);

        $ns      = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
        $pNodes  = $dom->getElementsByTagNameNS($ns, 'p');
        $result  = ['paragraphs' => [], 'wordCount' => 0];
        $listStack = []; // tracks which list items belong to which list
        $currentList = null;

        foreach ($pNodes as $p) {
            // Detect paragraph style (heading level)
            $pPr = $p->getElementsByTagNameNS($ns, 'pPr')->item(0);
            $styleName = '';
            if ($pPr) {
                $pStyle = $pPr->getElementsByTagNameNS($ns, 'pStyle')->item(0);
                if ($pStyle) {
                    $styleName = $pStyle->getAttribute('w:val') ?: '';
                }
            }

            // Collect all text runs
            $rNodes = $p->getElementsByTagNameNS($ns, 'r');
            $text = '';
            foreach ($rNodes as $r) {
                $tNodes = $r->getElementsByTagNameNS($ns, 't');
                foreach ($tNodes as $t) {
                    $text .= $t->nodeValue ?? '';
                }
            }

            $text = trim($text);
            if ($text === '') {
                continue;
            }

            $result['wordCount'] += str_word_count($text);

            // Determine paragraph type
            $type     = 'paragraph';
            $level    = 0;
            $items    = [];

            // Heading detection by style name (Heading1-6)
            if (preg_match('/^Heading(\d)$/', $styleName, $m)) {
                $type  = 'heading';
                $level = (int)$m[1];
            }
            // Fallback: detect by text starting with # for markdown-like content
            elseif (preg_match('/^(#{1,6})\s+(.+)/', $text, $m)) {
                $type  = 'heading';
                $level = strlen($m[1]);
                $text  = $m[2];
            }
            // List item
            elseif (preg_match('/^[-*•]\s+(.+)/', $text, $m)) {
                $type  = 'list-item';
                $text  = $m[1];
                $listType = 'ul';
            }
            elseif (preg_match('/^\d+\.\s+(.+)/', $text, $m)) {
                $type    = 'list-item';
                $text    = $m[1];
                $listType = 'ol';
            }
            // Code block (indented or monospace style)
            elseif ($this->isCodeParagraph($p, $dom, $ns)) {
                $type = 'code';
            }

            $result['paragraphs'][] = [
                'type'  => $type,
                'level' => $level,
                'text'  => $text,
                'items' => $items,
            ];
        }

        // Post-process: group consecutive list items into list blocks
        $result['paragraphs'] = $this->groupLists($result['paragraphs']);

        return $result;
    }

    private function isCodeParagraph($p, $dom, string $ns): bool
    {
        // Check for indentation (m:lInd) or preformatted style
        $pPr = $p->getElementsByTagNameNS($ns, 'pPr')->item(0);
        if ($pPr) {
            $lInd = $pPr->getElementsByTagNameNS($ns, 'ind')->item(0);
            if ($lInd) {
                $left = $lInd->getAttribute('w:left') ?: $lInd->getAttribute('w:lInd');
                if ($left && (int)$left >= 720) { // 720 twips = 0.5 inch indent
                    return true;
                }
            }
        }
        return false;
    }

    private function groupLists(array $paragraphs): array
    {
        $grouped = [];
        $i = 0;
        $n = count($paragraphs);

        while ($i < $n) {
            $p = $paragraphs[$i];

            if ($p['type'] === 'list-item') {
                // Collect consecutive list items
                $listType = 'ul';
                if (isset($p['_listType'])) {
                    $listType = $p['_listType'];
                }
                $items = [];
                while ($i < $n && $paragraphs[$i]['type'] === 'list-item') {
                    $items[] = $paragraphs[$i]['text'];
                    $i++;
                }
                $grouped[] = ['type' => 'list', 'listType' => $listType, 'items' => $items];
            } else {
                $grouped[] = $p;
                $i++;
            }
        }

        return $grouped;
    }

    /**
     * Reset slugify state before generating new content.
     * Call this before buildToc() + renderSemanticHtml() sequence.
     */
    private function resetSlugifyState(): void
    {
        $this->slugifySeen = [];
        $this->slugifyCounters = [];
    }

    /**
     * Build a table of contents from structured paragraphs.
     */
    private function buildToc(array $docxData): array
    {
        $toc = [];

        foreach ($docxData['paragraphs'] as $idx => $p) {
            if ($p['type'] === 'heading') {
                $id = $this->slugify($p['text']);
                $toc[] = [
                    'id'    => $id,
                    'level' => $p['level'],
                    'text'  => $p['text'],
                ];
            }
        }

        return $toc;
    }

    /**
     * Render structured paragraphs into semantic HTML.
     */
    private function renderSemanticHtml(array $docxData, int $articleId): string
    {
        $html = '';

        foreach ($docxData['paragraphs'] as $p) {
            switch ($p['type']) {
                case 'heading':
                    $level  = min((int)$p['level'], 6);
                    $text   = htmlspecialchars($p['text'], ENT_QUOTES, 'UTF-8');
                    $id     = $this->slugify($p['text']);
                    $html  .= "<h{$level} id=\"{$id}\">{$text}</h{$level}>\n";
                    break;

                case 'list':
                    $tag    = $p['listType'] === 'ol' ? 'ol' : 'ul';
                    $items  = '';
                    foreach ($p['items'] as $item) {
                        $safeText = htmlspecialchars($item, ENT_QUOTES, 'UTF-8');
                        $items   .= "<li>{$safeText}</li>\n";
                    }
                    $html .= "<{$tag}>\n{$items}</{$tag}>\n";
                    break;

                case 'code':
                    $safeText = htmlspecialchars($p['text'], ENT_QUOTES, 'UTF-8');
                    $html    .= "<pre><code>{$safeText}</code></pre>\n";
                    break;

                case 'paragraph':
                default:
                    $safeText = htmlspecialchars($p['text'], ENT_QUOTES, 'UTF-8');
                    $html    .= "<p>{$safeText}</p>\n";
                    break;
            }
        }

        return $html;
    }

    private function slugify(string $text): string
    {
        // Remove special chars, transliterate if possible, lowercase, hyphens
        $text = preg_replace('/[^a-zA-Z0-9\s\p{L}]/u', '', $text);
        $text = trim($text);
        // Cyrillic transliteration
        $cyr = [
            'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e',
            'ж'=>'zh','з'=>'z','и'=>'i','й'=>'j','к'=>'k','л'=>'l','м'=>'m',
            'н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u',
            'ф'=>'f','х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'shch',
            'ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
            'А'=>'a','Б'=>'b','В'=>'v','Г'=>'g','Д'=>'d','Е'=>'e','Ё'=>'e',
            'Ж'=>'zh','З'=>'z','И'=>'i','Й'=>'j','К'=>'k','Л'=>'l','М'=>'m',
            'Н'=>'n','О'=>'o','П'=>'p','Р'=>'r','С'=>'s','Т'=>'t','У'=>'u',
            'Ф'=>'f','Х'=>'h','Ц'=>'c','Ч'=>'ch','Ш'=>'sh','Щ'=>'shch',
            'Ъ'=>'','Ы'=>'y','Ь'=>'','Э'=>'e','Ю'=>'yu','Я'=>'ya',
        ];
        $text = strtr($text, $cyr);
        $text = strtolower($text);
        $text = preg_replace('/\s+/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        $text = trim($text, '-');

        // Ensure unique with index suffix if duplicate
        if (isset($this->slugifySeen[$text])) {
            $this->slugifyCounters[$text] = ($this->slugifyCounters[$text] ?? 1) + 1;
            $text = $text . '-' . $this->slugifyCounters[$text];
        }
        $this->slugifySeen[$text] = true;

        return $text;
    }

    private function readDocxFallback(string $filepath): string
    {
        $content = file_get_contents($filepath);

        if (preg_match_all('/<w:t[^>]*>(.*?)<\/w:t>/s', $content, $matches)) {
            $text = implode('', $matches[1]);
            $text = preg_replace('/\s+/', ' ', $text);
            return trim($text);
        }

        return '';
    }
}