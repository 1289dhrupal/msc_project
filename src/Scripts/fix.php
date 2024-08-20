<?php

declare(strict_types=1);

namespace MscProject\Scripts;

require 'vendor/autoload.php';

use Dotenv\Dotenv;
use MscProject\Models\Commit;
use MscProject\Services\GithubService;
use MscProject\Services\GitAnalysisService;
use MscProject\Repositories\GitRepository;
use MscProject\Routing\Orchestrator;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Create the Orchestrator with the configuration
$gitRepository = Orchestrator::getInstance()->get(GitRepository::class);

$commit_sql = "SELECT * FROM commits, commit_analysis WHERE id = commit_id";

for ($i = 1; $i < 185; $i++) {
    $commit = $gitRepository->getCommitById($i);
    $commit_analysis = $gitRepository->getCommitAnalysisById($i);

    mergeCommitFiles($commit, $commit_analysis);
}

function mergeCommitFiles(Commit $commit, string $commit_analysis)
{
    $c_files = json_decode($commit->getFiles(), true);
    $ca = json_decode($commit_analysis, true);
    $ca_files = $ca['commit_details']['files'];

    $c_files = array_combine(array_column($c_files, 'sha'), $c_files);
    $ca_files = array_combine(array_column($ca_files, 'sha'), $ca_files);

    foreach ($c_files as $sha => $val) {
        $c_files[$sha] = array_merge($ca_files[$sha], $c_files[$sha]);
    }

    echo json_encode($c_files, JSON_PRETTY_PRINT, JSON_HEX_QUOT);

    die();
}
