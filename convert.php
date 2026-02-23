<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'vendor/autoload.php';
require_once 'logger.php';

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Link;
use PhpOffice\PhpWord\Element\Title;
use PhpOffice\PhpWord\Element\Image;

const DOCX_FILE_PATH = './articlesFolder/';

$logger = new Logger('docx_parser.log');

function flushCode(&$html, &$codeBuffer) {
    global $logger;
    if (!empty($codeBuffer)) {
        $html .= "<pre class='code-block'><code>" . $codeBuffer . "</code></pre>";
        $codeBuffer = '';
        $logger->debug('Блок кода добавлен в HTML');
    }
}

function isCode($text, $fontName = null) {
    $codeFonts = ['Courier New', 'Consolas', 'Monaco'];
    return ($fontName && in_array($fontName, $codeFonts)) || preg_match('/[{}()\[\]<>$]/', $text);
}

function getHeadingLevel($size) {
    if ($size >= 28) return 1;
    if ($size >= 24) return 2;
    if ($size >= 20) return 3;
    if ($size >= 18) return 4;
    if ($size >= 16) return 5;
    return 6;
}

function getArticleFromFile($docxFile = null) {
    global $logger;

    if (!$docxFile) {
        $logger->warning('Попытка загрузки без указания файла');
        return false;
    }
    
    $logger->info('Начало загрузки файла: ' . $docxFile);

    try {
        $phpWord = IOFactory::load(DOCX_FILE_PATH . $docxFile);
        $html = '';
        $codeBuffer = '';
        
        $stats = [
            'titles' => 0,
            'headings' => 0,
            'links_top' => 0,
            'textruns' => 0,
            'links_in_text' => 0,
            'code_blocks' => 0
        ];

        foreach ($phpWord->getSections() as $sectionIndex => $section) {
            $logger->debug('Обработка секции ' . ($sectionIndex + 1));
            
            foreach ($section->getElements() as $elementIndex => $element) {
                
                // Обработка заголовков на верхнем уровне
                if ($element instanceof Title) {
                    flushCode($html, $codeBuffer);
                    $text = $element->getText();
                    $depth = $element->getDepth();
                    $level = min($depth, 6);
                    $html .= "<h{$level} class='doc-title doc-title-{$level}'>" . htmlspecialchars($text) . "</h{$level}>";
                    $stats['titles']++;
                    $logger->debug('Найден Title заголовок уровня ' . $level . ': "' . $text . '"');
                }
                
                // Обработка ссылок на верхнем уровне
                elseif ($element instanceof Link) {
                    flushCode($html, $codeBuffer);
                    $url = $element->getSource();
                    $text = $element->getText() ?: "ссылка";
                    $html .= "<p><a href='" . htmlspecialchars($url) . "' class='article-link' target='_blank'>" . htmlspecialchars($text) . "</a></p>";
                    $stats['links_top']++;
                    $logger->debug('Найдена ссылка верхнего уровня: "' . $text . '" -> ' . $url);
                }
                
                // Обработка TextRun
                elseif ($element instanceof TextRun) {
                    $fullText = '';
                    $hasLink = false;
                    $codeDetected = false;
                    $isHeading = false;
                    $headingLevel = 6;
                    $maxFontSize = 0;
                    $stats['textruns']++;
                    $partCount = 0;

                    foreach ($element->getElements() as $part) {
                        $partCount++;
                        
                        if ($part instanceof Text) {
                            $partText = $part->getText();
                            $fullText .= $partText;
                            
                            // Проверяем на код
                            if (!$codeDetected && $part->getFontStyle()) {
                                $fontName = $part->getFontStyle()->getName();
                                $codeDetected = isCode($fullText, $fontName);
                            }
                            
                            // Собираем информацию о размере шрифта
                            if ($part->getFontStyle()) {
                                $fontStyle = $part->getFontStyle();
                                $size = method_exists($fontStyle, 'getSize') ? $fontStyle->getSize() : 0;
                                
                                if ($size > $maxFontSize) {
                                    $maxFontSize = $size;
                                }
                            }
                            
                            // Логируем текст
                            $logText = substr($partText, 0, 50) . (strlen($partText) > 50 ? '...' : '');
                            $logger->debug('Текст часть ' . $partCount . ': "' . $logText . '"');
                        }
                        
                        if ($part instanceof Link) {
                            $hasLink = true;
                            $stats['links_in_text']++;
                            $url = $part->getSource();
                            $text = $part->getText() ?: $url;
                            $fullText .= "<a href='" . htmlspecialchars($url) . "' class='article-link' target='_blank'>" . htmlspecialchars($text) . "</a>";
                            $logger->debug('Ссылка внутри текста: "' . $text . '" -> ' . $url);
                        }
                        
                    }
                    
                    // Дополнительная проверка на код
                    if (!$codeDetected) {
                        $codeDetected = isCode(strip_tags($fullText));
                    }
                    
                    // ОПРЕДЕЛЕНИЕ ЗАГОЛОВКА - ТОЛЬКО ПО РАЗМЕРУ (без проверки на жирность)
                    if (!$codeDetected && !$hasLink && $maxFontSize >= 16) {
                        $isHeading = true;
                        $headingLevel = getHeadingLevel($maxFontSize);
                    }
                    
                    // Логируем результат анализа
                    $logger->debug('Анализ TextRun: длина=' . strlen($fullText) . 
                                 ', hasLink=' . ($hasLink ? 'да' : 'нет') . 
                                 ', isCode=' . ($codeDetected ? 'да' : 'нет') .
                                 ', isHeading=' . ($isHeading ? 'да (уровень ' . $headingLevel . ')' : 'нет') .
                                 ', maxFontSize=' . $maxFontSize);
                    
                    // Приоритет: заголовок > код > обычный текст
                    if ($isHeading && !$hasLink && !$codeDetected) {
                        flushCode($html, $codeBuffer);
                        $html .= "<h{$headingLevel} class='doc-title doc-title-{$headingLevel}'>" . htmlspecialchars(strip_tags($fullText)) . "</h{$headingLevel}>";
                        $stats['headings']++;
                        $logger->debug('>>> Текст определен как ЗАГОЛОВОК уровня ' . $headingLevel . ' (размер=' . $maxFontSize . ')');
                    }
                    elseif ($codeDetected && !$hasLink) {
                        $codeBuffer .= $fullText . "\n"; // Сохраняем HTML теги
                        $stats['code_blocks']++;
                        $logger->debug('Текст определен как код, добавлен в буфер');
                    } else {
                        flushCode($html, $codeBuffer);
                        $html .= "<p class='doc-text'>" . ($hasLink ? $fullText : htmlspecialchars($fullText)) . "</p>";
                        $logger->debug('Текст добавлен как параграф');
                    }
                }
                
                // Пропускаем изображения (Image) - они не обрабатываются
                elseif ($element instanceof Image) {
                    $logger->debug('Изображение пропущено (обработка отключена)');
                    continue;
                }
                
                // Логируем неизвестные типы элементов
                else {
                    $logger->debug('Необработанный тип элемента: ' . get_class($element));
                }
            }
        }

        flushCode($html, $codeBuffer);
        
        // Итоговая статистика
        $logger->info('Обработка завершена. Статистика:');
        $logger->info('  - Title заголовков: ' . $stats['titles']);
        $logger->info('  - Заголовков по стилю: ' . $stats['headings']);
        $logger->info('  - Ссылок верхнего уровня: ' . $stats['links_top']);
        $logger->info('  - Текстовых блоков (TextRun): ' . $stats['textruns']);
        $logger->info('  - Ссылок внутри текста: ' . $stats['links_in_text']);
        $logger->info('  - Блоков кода: ' . $stats['code_blocks']);
        
        return $html;
        
    } catch (Exception $e) {
        $logger->error('Ошибка при обработке файла ' . $docxFile . ': ' . $e->getMessage());
        return 'Ошибка: ' . $e->getMessage();
    }
}

?>