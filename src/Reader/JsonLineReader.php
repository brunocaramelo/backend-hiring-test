<?php

declare(strict_types=1);

namespace BatchDataImporter\Reader;

use RuntimeException;

class JsonLineReader
{
    public function read(string $path): iterable
    {
        if (!file_exists($path)) {
            throw new RuntimeException("Input file not found: {$path}");
        }

        $handle = fopen($path, 'r');
        
        if ($handle === false) {
            throw new RuntimeException("Cannot open file: {$path}");
        }

        try {
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                $data = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                
                yield $data;
            }
        } finally {
            fclose($handle);
        }
    }
}