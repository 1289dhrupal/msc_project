<?php

function analyzeCommit(int $commitId, array $commit, array $commitDetails): ?CommitAnalysis
    {
        $status = [];
        $keys = [];
        for ($i = 1; $i <= 184; $i++) {
            $files = $this->gitRepository->getCommitById($i)->getFiles();
            $files = json_decode($files, true);
            foreach ($files as $file) {
                $status[] = $file['status'];
                $status = array_unique($status);
                $keys = array_merge($keys, array_keys($file));
                $keys = array_unique($keys);
            }
        }

        print_r([$status, $keys]);
        die();
        // Split files based on status
        foreach ($commitDetails['files'] as $file) {
            $fileData = [
                'sha' => $file['sha'],
                'patch' => $file['patch'],
                'additions' => $file['additions'],
                'deletions' => $file['deletions'],
                'status' => $file['status']
            ];

            switch ($file['status']) {
                case 'removed':
                    $files['removed'][] = $fileData;
                    break;
                case 'renamed':
                    $files['renamed'][] = $fileData;
                    break;
                case 'modified':
                    $files['modified'][] = $fileData;
                    break;
                case 'updated':
                    $files['updated'][] = $fileData;
                    break;
                default:
                    $files['other'][] = $fileData;
                    break;
            }
        }

        print_r($files);

        $o = [
            "message" => $commitDetails['commit']['message'],
            "additions" => $commitDetails['stats']['additions'],
            "deletions" => $commitDetails['stats']['deletions'],
            "total" => $commitDetails['stats']['total'],
            "files" => $files
        ];



        echo json_encode($o);
        die();
        // Read commit details from sample_input.json
        $commit_details_json = file_get_contents(__DIR__ . '/sample_input.json');
        $commit_details = json_decode($commit_details_json, true);

        // Generate AI report
        $response = $this->generateAiReport($commit_details);
        print_r($response);

        return null;
    }
