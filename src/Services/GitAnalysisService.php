<?php

declare(strict_types=1);

namespace MscProject\Services;

use RuntimeException;

class GitAnalysisService
{

    public function __construct() {}

    public function analyzeCommit(array $commitDetailsFiles, string $commitMessage): array
    {
        // Generate AI report
        $commitAnalysisJson = $this->generateAiReport([
            "files" => $commitDetailsFiles,
            "commit_message" => $commitMessage
        ]);

        $commitAnalysis = json_decode($commitAnalysisJson, true);

        if ($commitAnalysis === null) {
            throw new RuntimeException("Failed to decode AI report JSON.");
        }

        $commitDetailsFiles = $this->mergeCommitFiles($commitDetailsFiles, $commitAnalysis['commit_details']['files'] ?? []);
        $response = [
            "files" => $commitDetailsFiles,
            "stats" => [
                'number_of_comment_lines' => $commitAnalysis['commit_details']['number_of_comment_lines'] ?? 0,
                'commit_changes_quality_score' => $commitAnalysis['commit_details']['commit_changes_quality_score'] ?? 0,
                'commit_message_quality_score' => $commitAnalysis['commit_details']['commit_message_quality_score'] ?? 0,
            ]
        ];

        return $response;
    }

    public function mergeCommitFiles(array $commitFiles, array $commitAnalysisFiles): array
    {
        $commitFiles = array_combine(array_column($commitFiles, 'sha'), $commitFiles);
        $commitAnalysisFiles = array_combine(array_column($commitAnalysisFiles, 'sha'), $commitAnalysisFiles);

        foreach ($commitFiles as $sha => $val) {
            if (isset($commitAnalysisFiles[$sha])) {
                $commitFiles[$sha] = array_merge($commitAnalysisFiles[$sha], $commitFiles[$sha]);
            } else {
                $commitFiles[$sha]['quality_score'] = 0;
                $commitFiles[$sha]['modification_type'] = $this->getModificationType($commitFiles[$sha]);
            }
        }

        return array_values($commitFiles);
    }

    private function getModificationType(array $file): string
    {
        switch ($file['status']) {
            case 'added':
                return isset($file['changes']) && $file['changes'] == 0 ? 'whitespace_changes' : 'added_code';
            case 'modified':
                return 'updated_code';
            case 'removed':
                return 'removed_code';
            case 'renamed':
                if (!isset($file['changes']) || $file['changes'] == 0) {
                    return 'renamed_elements';
                }
                return $file['changes'] == $file['additions'] ? 'added_code' : 'removed_code';
            default:
                return 'unknown';
        }
    }

    private function generateAiReport(array $commitDetails): string
    {
        if ($_ENV['ENV'] === 'dev') {
            // TODO: Remove this line and use the actual commit details
            return file_get_contents(__DIR__ . '/output.json');
        }

        $prompt = "
            As a software quality analyst, your task is to evaluate the quality of a commit based on the details provided. Follow the given JSON schema to create an object for the commit that includes the overall commit quality score, the quality score of the commit message, and details about the file changes. Skip any CSV, JSON, or automatically generated files in your evaluation. Use the following guidelines to assign scores:

            Scoring Guidelines:
                1. Very Low Score (0-1):
                    - Assign a very low score if the commit only involves:
                        - Deleting lines
                        - Renaming or moving files
                        - Whitespace changes (e.g., spaces to tabs or vice versa, formatting changes)
                2. Low Score (1-2):
                    - Assign a low score if the commit only involves:
                        - Renaming variables or classes
                        - Changing operators (e.g., a += 1 to a = a + 1, and to &&)
                3. Medium Score (3-5):
                    - Assign a medium score for commits that involve:
                        - Minor updates or improvements to existing code (e.g., optimizing a function, improving readability)
                4. High Score (6-8):
                    - Assign a high score for commits that involve:
                        - Significant updates to existing code (e.g., major refactoring, improving performance significantly)
                5. Very High Score (9-10):
                    - Assign a very high score for commits that involve:
                        - Adding new code (e.g., new features, new modules)
                        - Major improvements or additions to existing code that enhance functionality or performance
            Important Note:
                If the commit includes both low-scoring changes (e.g., renaming variables) and high-scoring changes (e.g., adding new code or significant updates), the overall score should reflect the higher impact of the significant changes.
                
            Commit details:
            " . json_encode($commitDetails);

        $function = json_decode(file_get_contents(__DIR__ . '/ai_integrations.json'), true);
        return OpenAiService::generateCompletion($prompt, $function);
    }

    public function getChangeStat(string $patch): array
    {
        $lines = explode("\n", $patch);
        $additions = 0;
        $deletions = 0;

        foreach ($lines as $line) {
            if (strpos($line, '@@') === 0) {
                continue;
            }

            if (strpos($line, '+') === 0 && strpos($line, '+++') !== 0) {
                $additions++;
            } elseif (strpos($line, '-') === 0 && strpos($line, '---') !== 0) {
                $deletions++;
            }
        }

        return [
            'additions' => $additions,
            'deletions' => $deletions,
            'changes' => $additions + $deletions
        ];
    }
}
