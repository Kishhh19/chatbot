<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Document Parser - Extracts text from uploaded documents
 * Supports: TXT, CSV, PDF, DOCX
 */
class DocumentParser {

    /**
     * Parse a document and return extracted text
     */
    public function parse(string $filePath, string $fileType): array {
        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => 'File not found: ' . basename($filePath)];
        }

        try {
            switch (strtolower($fileType)) {
                case 'txt':
                    return $this->parseTxt($filePath);
                case 'csv':
                    return $this->parseCsv($filePath);
                case 'pdf':
                    return $this->parsePdf($filePath);
                case 'docx':
                    return $this->parseDocx($filePath);
                default:
                    return ['success' => false, 'error' => 'Unsupported file type: ' . $fileType];
            }
        } catch (Exception $e) {
            app_log('error', 'Document parse failed', ['file' => basename($filePath), 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Failed to parse document: ' . $e->getMessage()];
        }
    }

    /**
     * Parse plain text file
     */
    private function parseTxt(string $filePath): array {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return ['success' => false, 'error' => 'Could not read file.'];
        }

        // Detect and convert encoding
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        return ['success' => true, 'text' => $this->cleanText($content)];
    }

    /**
     * Parse CSV file - converts to readable text
     */
    private function parseCsv(string $filePath): array {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ['success' => false, 'error' => 'Could not open CSV file.'];
        }

        $lines = [];
        $headers = null;
        $rowCount = 0;

        while (($row = fgetcsv($handle, 10000, ',')) !== false) {
            if ($headers === null) {
                $headers = $row;
            } else {
                if (count($row) === count($headers)) {
                    $combined = array_combine($headers, $row);
                    $line = implode(' | ', array_map(
                        fn($k, $v) => "$k: $v",
                        array_keys($combined),
                        $combined
                    ));
                    $lines[] = $line;
                    $rowCount++;
                }
                if ($rowCount > 5000) break; // Safety limit
            }
        }
        fclose($handle);

        $text = "CSV Data (" . count($lines) . " rows):\n\n" . implode("\n", $lines);
        return ['success' => true, 'text' => $this->cleanText($text)];
    }

    /**
     * Parse PDF file using multiple strategies
     */
    private function parsePdf(string $filePath): array {
        // Strategy 1: Try pdftotext (poppler-utils) if available
        if ($this->commandExists('pdftotext')) {
            $output = [];
            $returnCode = 0;
            exec('pdftotext -enc UTF-8 ' . escapeshellarg($filePath) . ' - 2>&1', $output, $returnCode);
            if ($returnCode === 0 && !empty($output)) {
                $text = implode("\n", $output);
                if (strlen(trim($text)) > 10) {
                    return ['success' => true, 'text' => $this->cleanText($text)];
                }
            }
        }

        // Strategy 2: Native PHP PDF text extraction (handles simple PDFs)
        $text = $this->extractPdfTextNative($filePath);
        if ($text !== false && strlen(trim($text)) > 10) {
            return ['success' => true, 'text' => $this->cleanText($text)];
        }

        // Strategy 3: Return file metadata with instructions
        return [
            'success' => true,
            'text'    => "PDF Document: " . basename($filePath) . "\n\nNote: Full text extraction requires pdftotext (poppler-utils). Please install it or convert the PDF to TXT format for better results.",
            'warning' => 'Limited PDF extraction. Install pdftotext for full text extraction.',
        ];
    }

    /**
     * Native PHP PDF text extraction (basic, works for simple PDFs)
     */
    private function extractPdfTextNative(string $filePath): string|false {
        $content = file_get_contents($filePath);
        if ($content === false) return false;

        // Extract text between BT and ET markers
        $text = '';
        preg_match_all('/BT\s*(.*?)\s*ET/s', $content, $matches);

        foreach ($matches[1] as $block) {
            // Extract text from Tj, TJ, ' commands
            preg_match_all('/\(((?:[^()\\\\]|\\\\.)*)\)\s*(?:Tj|\'|\")/u', $block, $tjMatches);
            foreach ($tjMatches[1] as $str) {
                $decoded = $this->decodePdfString($str);
                if ($decoded) $text .= $decoded . ' ';
            }

            // TJ arrays
            preg_match_all('/\[((?:[^\[\]]|\((?:[^()]*)\))*)\]\s*TJ/u', $block, $tjArrayMatches);
            foreach ($tjArrayMatches[1] as $arr) {
                preg_match_all('/\(((?:[^()\\\\]|\\\\.)*)\)/u', $arr, $strMatches);
                foreach ($strMatches[1] as $str) {
                    $decoded = $this->decodePdfString($str);
                    if ($decoded) $text .= $decoded;
                }
                $text .= ' ';
            }
        }

        return $text ?: false;
    }

    /**
     * Decode PDF string escape sequences
     */
    private function decodePdfString(string $str): string {
        $str = str_replace(['\\n', '\\r', '\\t'], ["\n", "\r", "\t"], $str);
        $str = preg_replace_callback('/\\\\([0-7]{1,3})/', function($m) {
            return chr(octdec($m[1]));
        }, $str);
        $str = str_replace(['\\\\', '\\(', '\\)'], ['\\', '(', ')'], $str);
        return mb_convert_encoding($str, 'UTF-8', 'Windows-1252');
    }

    /**
     * Parse DOCX file (ZIP-based XML)
     */
    private function parseDocx(string $filePath): array {
        if (!class_exists('ZipArchive')) {
            return ['success' => false, 'error' => 'ZipArchive PHP extension is required for DOCX parsing. Please enable it in php.ini.'];
        }

        $zip = new ZipArchive();
        $result = $zip->open($filePath);

        if ($result !== true) {
            return ['success' => false, 'error' => 'Could not open DOCX file (not a valid ZIP).'];
        }

        // Read word/document.xml
        $xmlContent = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xmlContent === false) {
            return ['success' => false, 'error' => 'Could not find document content in DOCX file.'];
        }

        // Parse XML
        $xml = simplexml_load_string($xmlContent, 'SimpleXMLElement', LIBXML_NOENT | LIBXML_NOERROR);
        if ($xml === false) {
            return ['success' => false, 'error' => 'Could not parse DOCX XML content.'];
        }

        // Register namespace
        $xml->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        // Extract text from paragraphs
        $paragraphs = [];
        foreach ($xml->xpath('//w:p') as $paragraph) {
            $paraText = '';
            foreach ($paragraph->xpath('.//w:t') as $textNode) {
                $paraText .= (string)$textNode;
            }
            if (trim($paraText)) {
                $paragraphs[] = trim($paraText);
            }
        }

        $text = implode("\n\n", $paragraphs);
        return ['success' => true, 'text' => $this->cleanText($text)];
    }

    /**
     * Split text into overlapping chunks for RAG
     */
    public function chunkText(string $text, int $chunkSize = CHUNK_SIZE, int $overlap = CHUNK_OVERLAP): array {
        $text = trim($text);
        if (empty($text)) return [];

        $chunks = [];

        // Split by paragraphs first for better context
        $paragraphs = preg_split('/\n\s*\n/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $currentChunk = '';

        foreach ($paragraphs as $para) {
            $para = trim($para);
            if (empty($para)) continue;

            // If adding this paragraph would exceed chunk size
            if (strlen($currentChunk) + strlen($para) + 2 > $chunkSize && !empty($currentChunk)) {
                $chunks[] = trim($currentChunk);

                // Start new chunk with overlap from end of previous
                if ($overlap > 0 && strlen($currentChunk) > $overlap) {
                    $currentChunk = substr($currentChunk, -$overlap) . "\n\n" . $para;
                } else {
                    $currentChunk = $para;
                }
            } else {
                $currentChunk .= ($currentChunk ? "\n\n" : '') . $para;
            }

            // If single paragraph exceeds chunk size, force split
            if (strlen($currentChunk) > $chunkSize * 1.5) {
                $subChunks = str_split($currentChunk, $chunkSize);
                foreach ($subChunks as $sub) {
                    if (strlen(trim($sub)) > 50) {
                        $chunks[] = trim($sub);
                    }
                }
                $currentChunk = '';
            }
        }

        if (!empty(trim($currentChunk))) {
            $chunks[] = trim($currentChunk);
        }

        // Final pass: remove empty or very short chunks
        return array_values(array_filter($chunks, fn($c) => strlen(trim($c)) > 30));
    }

    /**
     * Clean and normalize extracted text
     */
    private function cleanText(string $text): string {
        // Normalize line endings
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Remove null bytes and control characters (keep tab and newline)
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);

        // Normalize multiple blank lines to double newline
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        // Remove excessive whitespace within lines
        $text = preg_replace('/[ \t]{2,}/', ' ', $text);

        // Ensure valid UTF-8
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        return trim($text);
    }

    /**
     * Check if a system command exists
     */
    private function commandExists(string $command): bool {
        $output = shell_exec(PHP_OS_FAMILY === 'Windows'
            ? "where $command 2>nul"
            : "which $command 2>/dev/null"
        );
        return !empty(trim($output ?? ''));
    }
}
