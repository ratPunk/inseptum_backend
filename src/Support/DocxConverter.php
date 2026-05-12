<?php
declare(strict_types=1);

namespace App\Support;

use App\Core\Logger;
use PhpOffice\PhpWord\Element\Image;
use PhpOffice\PhpWord\Element\Link;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Title;
use PhpOffice\PhpWord\IOFactory;
use Throwable;

/**
 * Конвертер .docx -> HTML.
 *
 * Порт legacy convert.php в OOP-стиль. Без глобальных функций и без
 * требования require_once.
 */
class DocxConverter
{
    private string $uploadDir;
    private ?Logger $logger;

    public function __construct(string $uploadDir, ?Logger $logger = null)
    {
        $this->uploadDir = rtrim($uploadDir, '/\\') . '/';
        $this->logger    = $logger;
    }

    /**
     * Конвертирует docx-файл в HTML. Возвращает пустую строку, если не удалось.
     */
    public function convert(string $fileName): string
    {
        if ($fileName === '') {
            $this->log('warning', 'Попытка загрузки без указания файла');
            return '';
        }

        $path = $this->uploadDir . $fileName;
        if (!is_file($path)) {
            $this->log('warning', 'Файл не найден: ' . $path);
            return '';
        }

        $this->log('info', 'Начало загрузки файла: ' . $fileName);

        try {
            $phpWord = IOFactory::load($path);
            $html = '';
            $codeBuffer = '';

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if ($element instanceof Title) {
                        $this->flushCode($html, $codeBuffer);
                        $level = min((int)$element->getDepth(), 6);
                        $html .= "<h{$level} class='doc-title doc-title-{$level}'>"
                              . htmlspecialchars((string)$element->getText())
                              . "</h{$level}>";
                    } elseif ($element instanceof Link) {
                        $this->flushCode($html, $codeBuffer);
                        $url  = (string)$element->getSource();
                        $text = (string)($element->getText() ?: 'ссылка');
                        $html .= "<p><a href='" . htmlspecialchars($url)
                              . "' class='article-link' target='_blank'>"
                              . htmlspecialchars($text) . "</a></p>";
                    } elseif ($element instanceof TextRun) {
                        $this->renderTextRun($element, $html, $codeBuffer);
                    } elseif ($element instanceof Image) {
                        $this->flushCode($html, $codeBuffer);
                        $img = $this->renderImage($element);
                        if ($img !== '') {
                            $html .= $img;
                        }
                    }
                }
            }

            $this->flushCode($html, $codeBuffer);
            return $html;
        } catch (Throwable $e) {
            $this->log('error', 'Ошибка при обработке файла ' . $fileName . ': ' . $e->getMessage());
            return '';
        }
    }

    private function renderTextRun(TextRun $element, string &$html, string &$codeBuffer): void
    {
        $fullText     = '';
        $hasLink      = false;
        $codeDetected = false;
        $maxFontSize  = 0;

        foreach ($element->getElements() as $part) {
            if ($part instanceof Text) {
                $partText  = (string)$part->getText();
                $fullText .= $partText;

                if ($part->getFontStyle()) {
                    $fontStyle = $part->getFontStyle();
                    if (!$codeDetected) {
                        $codeDetected = $this->isCode($fullText, $fontStyle->getName());
                    }
                    $size = method_exists($fontStyle, 'getSize') ? (int)$fontStyle->getSize() : 0;
                    if ($size > $maxFontSize) {
                        $maxFontSize = $size;
                    }
                }
            } elseif ($part instanceof Link) {
                $hasLink = true;
                $url     = (string)$part->getSource();
                $text    = (string)($part->getText() ?: $url);
                $fullText .= "<a href='" . htmlspecialchars($url)
                          . "' class='article-link' target='_blank'>"
                          . htmlspecialchars($text) . "</a>";
            } elseif ($part instanceof Image) {
                $img = $this->renderImage($part);
                if ($img !== '') {
                    $this->flushCode($html, $codeBuffer);
                    $html .= $img;
                }
            }
        }

        if (!$codeDetected) {
            $codeDetected = $this->isCode(strip_tags($fullText));
        }

        $isHeading = false;
        $headingLevel = 6;
        if (!$codeDetected && !$hasLink && $maxFontSize >= 16) {
            $isHeading = true;
            $headingLevel = $this->getHeadingLevel($maxFontSize);
        }

        if ($isHeading && !$hasLink && !$codeDetected) {
            $this->flushCode($html, $codeBuffer);
            $html .= "<h{$headingLevel} class='doc-title doc-title-{$headingLevel}'>"
                  . htmlspecialchars(strip_tags($fullText))
                  . "</h{$headingLevel}>";
        } elseif ($codeDetected && !$hasLink) {
            $codeBuffer .= $fullText . "\n";
        } else {
            $this->flushCode($html, $codeBuffer);
            $html .= "<p class='doc-text'>"
                  . ($hasLink ? $fullText : htmlspecialchars($fullText))
                  . "</p>";
        }
    }

    private function renderImage($image): string
    {
        if (!($image instanceof Image)) {
            return '';
        }

        try {
            if (method_exists($image, 'getImageStringData')) {
                $base64 = @$image->getImageStringData(true);
                if ($base64) {
                    $mime = method_exists($image, 'getImageType') ? $image->getImageType() : 'image/png';
                    if (!$mime) {
                        $mime = 'image/png';
                    }
                    return "<p class='doc-image-wrap'>"
                         . "<img class='doc-image' src='data:{$mime};base64,{$base64}' alt='' />"
                         . "</p>";
                }
            }

            $source = method_exists($image, 'getSource') ? $image->getSource() : null;
            if ($source && @is_file($source)) {
                $binary = @file_get_contents($source);
                if ($binary !== false) {
                    $info = @getimagesizefromstring($binary);
                    $mime = $info && !empty($info['mime']) ? $info['mime'] : 'image/png';
                    $base64 = base64_encode($binary);
                    return "<p class='doc-image-wrap'>"
                         . "<img class='doc-image' src='data:{$mime};base64,{$base64}' alt='' />"
                         . "</p>";
                }
            }
        } catch (Throwable $e) {
            $this->log('error', 'Ошибка при обработке изображения: ' . $e->getMessage());
        }
        return '';
    }

    private function flushCode(string &$html, string &$codeBuffer): void
    {
        if ($codeBuffer !== '') {
            $html .= "<pre class='code-block'><code>" . $codeBuffer . "</code></pre>";
            $codeBuffer = '';
        }
    }

    private function isCode(string $text, ?string $fontName = null): bool
    {
        $codeFonts = ['Courier New', 'Consolas', 'Monaco'];
        return ($fontName !== null && in_array($fontName, $codeFonts, true))
            || (bool)preg_match('/[{}()\[\]<>$]/', $text);
    }

    private function getHeadingLevel(int $size): int
    {
        if ($size >= 28) return 1;
        if ($size >= 24) return 2;
        if ($size >= 20) return 3;
        if ($size >= 18) return 4;
        if ($size >= 16) return 5;
        return 6;
    }

    private function log(string $level, string $message): void
    {
        if ($this->logger !== null) {
            $this->logger->log($level, $message);
        }
    }
}
