<?php

declare(strict_types=1);

namespace MscProject;

class Utils
{
    public static function maskToken(string $token): string
    {
        // Identify the position of the first delimiter ('_' or '-')
        $delimiterPosition = strcspn($token, '_-');

        if ($delimiterPosition === strlen($token)) {
            // If no delimiter is found, mask the entire token except the last 4 characters
            return str_repeat('*', strlen($token) - 4) . substr($token, -4);
        }

        // Split the token into parts: prefix, middle, and end
        $prefix = substr($token, 0, $delimiterPosition + 1);
        $end = substr($token, -4);
        $middle = substr($token, strlen($prefix), -4);

        // Mask the middle section, keeping 3 characters visible if possible
        $visibleMiddleChars = min(3, strlen($middle));
        $maskedLength = strlen($middle) - $visibleMiddleChars;

        // Replace the masked portion with **{count}**
        if ($maskedLength > 0) {
            $maskedMiddle = '**' . $maskedLength . '**';
        } else {
            $maskedMiddle = $middle; // No masking if the middle section is too short
        }

        // Show a few characters from the middle section
        $visibleMiddle = substr($middle, 0, $visibleMiddleChars);
        $maskedToken = $prefix . $visibleMiddle . $maskedMiddle . $end;

        return $maskedToken;
    }


    public static function isCodeFile(string $fileName, int $fileChanges): bool
    {
        // If the file should be skipped based on size or directory, return false
        if (self::shouldSkipFile($fileName, $fileChanges)) {
            return false;
        }

        // List of common code file extensions
        $codeExtensions = [
            'html',
            'css',
            'js',
            'py',
            'ipynb',
            'ejs',
            'java',
            'c',
            'cpp',
            'cs',
            'rb',
            'pl',
            'swift',
            'go',
            'sh',
            'bat',
            'ts',
            'xml',
            'sql',
            'r',
            'scala',
            'php',
            'php3',
            'groovy',
            'php4',
            'php5',
            'php7',
            'jsp',
            'aspx',
            'asp',
            'vue',
            'jsx',
            'tsx',
            'kt',
            'dart',
            'rs'
        ];

        // Check if the file's extension is in the list of code file extensions
        $extension = self::getFileExtension($fileName);
        return in_array($extension, $codeExtensions, true);
    }

    public static function getFileExtension(string $fileName): string
    {
        return strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    }

    private static function shouldSkipFile(string $filePath, int $fileChanges): bool
    {
        if ($fileChanges > 10000) {
            return true;
        }

        // Directories to exclude from processing
        $excludedDirectories = [
            'node_modules',
            '.venv',
            'venv',
            'env',
            '.env',
            'dist',
            'build',
            'vendor',
            '__pycache__',
            '.gradle',
            '.settings'
        ];

        // Normalize path separators for consistent checking
        $normalizedPath = str_replace('\\', '/', $filePath);

        foreach ($excludedDirectories as $dir) {
            if (strpos($normalizedPath, "/$dir/") !== false || strpos($normalizedPath, "/$dir") !== false) {
                return true;
            }
        }

        return false;
    }
}
