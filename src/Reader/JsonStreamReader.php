<?php

declare(strict_types=1);

namespace BatchDataImporter\Reader;

use JsonMachine\Items;
use RuntimeException;

class JsonStreamReader
{
    private string $buffer = '';
    private int $depth = 0;
    private bool $inString = false;
    private bool $escaped = false;

    public function read(string $path): iterable
    {
        if (!file_exists($path)) {
            throw new RuntimeException("Input file not found: {$path}");
        }

        $handle = fopen($path, 'rb');
        if (!$handle) {
            throw new RuntimeException("Cannot open file for reading: {$path}");
        }

        try {
            while (!feof($handle)) {
                $chunk = fread($handle, 8192);
                if ($chunk === false) {
                    break;
                }
                yield from $this->parseChunk($chunk);
            }
        } finally {
            fclose($handle);
        }
    }

    private function parseChunk(string $chunk): iterable
    {
        $length = strlen($chunk);
        for ($i = 0; $i < $length; $i++) {
            $char = $chunk[$i];

            $this->updateStringState($char);

            if (!$this->inString) {
                yield from $this->handleStructuralChar($char);
            } else {
                $this->appendToBuffer($char);
            }
        }
    }

    private function updateStringState(string $char): void
    {
        if ($char === '"' && !$this->escaped) {
            $this->inString = !$this->inString;
        }

        if ($char === '\\' && !$this->escaped) {
            $this->escaped = true;
        } else {
            $this->escaped = false;
        }
    }

    private function handleStructuralChar(string $char): iterable
    {
        if ($char === '{') {
            $this->depth++;
        }

        $this->appendToBuffer($char);

        if ($char === '}') {
            $this->depth--;
            if ($this->depth === 0) {
                yield from $this->flushBuffer();
            }
        }
    }

    private function appendToBuffer(string $char): void
    {
        if ($this->depth > 0) {
            $this->buffer .= $char;
        }
    }

    private function flushBuffer(): iterable
    {
        $item = json_decode($this->buffer, true);
        $this->buffer = '';

        if (json_last_error() === JSON_ERROR_NONE) {
            yield $item;
        }
    }
}