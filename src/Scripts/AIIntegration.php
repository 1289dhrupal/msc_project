<?php

// Read commit details from sample_input.json
$commit_details_json = file_get_contents(__DIR__ . '/sample_input.json');
$commit_details = json_decode($commit_details_json, true);

$response = _ai_generator($commit_details);

print_r($response);

function _ai_generator($commit_details)
{
    // get the function from sub_processes
    $function = json_decode(file_get_contents(__DIR__ . '/ai_integrations.json'), true);

    // prompt for AI
    $prompt = "
    As a software quality analyst, your task is to evaluate the quality of a commit based on the details provided. Follow the given JSON schema to create an object for the commit that includes the overall commit quality score, the quality score of the commit message, and details about the file changes. Use the following guidelines to assign scores:

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

    // organizing parameters for AI
    $function_calling = array(
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
    );

    // common parameters
    $common_parameter = array(
        "model" => "gpt-4-turbo",
        "temperature" => 0,
        "top_p" => 0,
        "frequency_penalty" => 0,
        "presence_penalty" => 0,
        "seed" => 123,
        "n" => 1,
    );

    // call OpenAI API
    $completion = _openai("chat/completions", array_merge($common_parameter, $function_calling));

    // TESTING
    // $completion = json_decode(file_get_contents("_ai_dummy_test_$sub_process.json"), true);

    // check if the response is valid
    if (!isset($completion['choices'])) {
        trigger_error("OpenAI error: \n" . json_encode($completion, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES) . "\n",  E_USER_ERROR);
    }

    // return the valid response
    return $completion['choices'][0]['message']["tool_calls"][0]['function']['arguments'];
}

function _openai($api, $data)
{
    $openai_api_key = 'sk-None-kH7c9sWonemSOm76xmMaT3BlbkFJKm2x6PzycH2ABBHkDMCL';

    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_URL => "https://api.openai.com/v1/" . $api,
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer " . $openai_api_key,
            "Content-Type: application/json"
        ),
        CURLOPT_POSTFIELDS => json_encode($data)
    ));

    $response = json_decode(curl_exec($ch), true);

    curl_close($ch);

    return $response;
}
