<?php

declare(strict_types=1);

namespace MscProject;

class Utils
{
    public static function maskToken(string $token): string
    {
        // Find the position of the first occurrence of '_' or '-'
        $delimiterPosition = strcspn($token, '_-');
        if ($delimiterPosition === false) {
            // If neither '_' nor '-' is found, return the original token
            return $token;
        }

        // Extract the prefix based on the first occurrence of '_' or '-'
        $prefix = substr($token, 0, $delimiterPosition + 1);

        // Keep the last 4 characters
        $end = substr($token, -4);

        // Get the middle part of the token
        $middle = substr($token, strlen($prefix), -4);

        // Determine how many characters to keep visible in the middle part
        $visibleMiddleChars = min(3, strlen($middle)); // Keep at least 3 characters visible
        $visibleMiddle = '';
        $maskedMiddle = str_repeat('*', strlen($middle));

        if ($visibleMiddleChars > 0) {
            // Randomly select characters to keep visible
            $randomKeys = array_rand(str_split($middle), $visibleMiddleChars);

            // Ensure $randomKeys is an array even if one key is selected
            if (!is_array($randomKeys)) {
                $randomKeys = [$randomKeys];
            }

            $visibleMiddleArray = str_split($middle);
            foreach ($randomKeys as $key) {
                $visibleMiddle .= $visibleMiddleArray[$key];
                $maskedMiddle[$key] = $visibleMiddleArray[$key];
            }
        }

        // Combine the masked token
        $maskedToken = $prefix . $maskedMiddle . $end;
        $maskedToken = preg_replace('/\*{3,}/', '***', $maskedToken);

        return $maskedToken;
    }

    public static function isCodeFile(string $fileName, int $fileChanges): bool
    {
        // List of common code file extensions
        $codeExtensions = [
            'php',
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

        // Extract the file extension
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Check if the extension is in the list of code file extensions
        return !Utils::shouldSkipFile($fileName, $fileChanges) && in_array($extension, $codeExtensions);
    }


    private static function shouldSkipFile(string $filePath, int $fileChanges): bool
    {
        if ($fileChanges > 10000) {
            return true;
        }

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

        // Normalize directory separators for consistency
        $normalizedPath = str_replace('\\', '/', $filePath);

        foreach ($excludedDirectories as $dir) {
            if (strpos($normalizedPath, "/$dir/") !== false || strpos($normalizedPath, "/$dir") !== false) {
                return true;
            }
        }

        return false;
    }
}
