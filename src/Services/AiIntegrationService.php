<?php

declare(strict_types=1);

namespace MscProject\Services;

use MscProject\Repositories\GitRepository;

class AiIntegrationService
{
    private GitRepository $gitRepository;

    public function __construct(GitRepository $gitRepository)
    {
        $this->gitRepository = $gitRepository;
    }

    private function _openai(string $api, array $data)
    {
        $openai_api_key = $_ENV['OPENAI_API_KEY'];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_URL => "https://api.openai.com/v1/" . $api,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $openai_api_key,
                "Content-Type: application/json"
            ],
            CURLOPT_POSTFIELDS => json_encode($data)
        ]);

        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        return $response;
    }

    public function generateAiReport(array $commit_details): string
    {
        $function = json_decode(file_get_contents(__DIR__ . '/ai_integrations.json'), true);

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
        ";

        $prompt .= " Commit details:\n" . json_encode($commit_details);

        $function_calling = [
            "messages" => [
                [
                    "role" => "user",
                    "content" => $prompt
                ]
            ],
            "tools" => $function,
            "tool_choice" => [
                "type" => "function",
                "function" => [
                    "name" => $function[0]['function']['name']
                ]
            ]
        ];

        $common_parameter = [
            "model" => "gpt-4-turbo",
            "temperature" => 0,
            "top_p" => 0,
            "frequency_penalty" => 0,
            "presence_penalty" => 0,
            "seed" => 123,
            "n" => 1,
        ];

        $completion = $this->_openai("chat/completions", array_merge($common_parameter, $function_calling));

        if (!isset($completion['choices'])) {
            trigger_error("OpenAI error: \n" . json_encode($completion, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES) . "\n",  E_USER_ERROR);
        }

        return $completion['choices'][0]['message']["tool_calls"][0]['function']['arguments'];
    }
}
