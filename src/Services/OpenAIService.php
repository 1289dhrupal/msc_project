<?php

declare(strict_types=1);

namespace MscProject\Services;

use RuntimeException;

class OpenAiService
{
    public static function generateCompletion(string $prompt, string $function): string
    {
        $api = "chat/completions";
        $data = [
            "model" => "gpt-4-turbo",
            "temperature" => 0,
            "top_p" => 0,
            "frequency_penalty" => 0,
            "presence_penalty" => 0,
            "seed" => 123,
            "n" => 1,
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

        return self::callOpenAiApi($api, $data);
    }

    private static function callOpenAiApi(string $api, array $data): string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_URL => "https://api.openai.com/v1/" . $api,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $_ENV['OPENAI_API_KEY'],
                "Content-Type: application/json"
            ],
            CURLOPT_POSTFIELDS => json_encode($data)
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new RuntimeException('CURL error: ' . curl_error($ch));
        }

        $decodedResponse = json_decode($response, true);
        curl_close($ch);

        if (!isset($decodedResponse['choices'])) {
            throw new RuntimeException("OpenAI API error: " . json_encode($decodedResponse));
        }

        return $decodedResponse['choices'][0]['message']["tool_calls"][0]['function']['arguments'] ?? '';
    }
}
