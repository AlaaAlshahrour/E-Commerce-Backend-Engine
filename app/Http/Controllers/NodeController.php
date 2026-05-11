<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class NodeController extends Controller
{
    private array $nodes = [
        'Node-1' => [
            'fpm' => 'app-node-1',
            'web' => 'app-node-1-web',
        ],
        'Node-2' => [
            'fpm' => 'app-node-2',
            'web' => 'app-node-2-web',
        ],
        'Node-3' => [
            'fpm' => 'app-node-3',
            'web' => 'app-node-3-web',
        ],
    ];

    public function status(): JsonResponse
    {
        $result = [];

        foreach ($this->nodes as $nodeName => $containers) {
            $webRunning = trim(shell_exec(
                    "docker inspect -f '{{.State.Running}}' {$containers['web']} 2>/dev/null"
                )) === 'true';

            $fpmRunning = trim(shell_exec(
                    "docker inspect -f '{{.State.Running}}' {$containers['fpm']} 2>/dev/null"
                )) === 'true';

            $result[$nodeName] = [
                'running'       => $webRunning && $fpmRunning,
                'web_running'   => $webRunning,
                'fpm_running'   => $fpmRunning,
                'web_container' => $containers['web'],
                'fpm_container' => $containers['fpm'],
            ];
        }

        return response()->json([
            'successful' => true,
            'node'       => env('NODE_NAME', gethostname()),
            'nodes'      => $result,
        ]);
    }

    public function stop(string $node): JsonResponse
    {
        if (!isset($this->nodes[$node])) {
            return response()->json([
                'successful' => false,
                'message'    => 'Node not found',
            ], 404);
        }

        // أوقف الـ web أولاً — هذا ما يرى NGINX
        shell_exec("docker stop {$this->nodes[$node]['web']} 2>/dev/null");
        shell_exec("docker stop {$this->nodes[$node]['fpm']} 2>/dev/null");

        return response()->json([
            'successful' => true,
            'message'    => "{$node} stopped",
            'node'       => env('NODE_NAME', gethostname()),
        ]);
    }

    public function start(string $node): JsonResponse
    {
        if (!isset($this->nodes[$node])) {
            return response()->json([
                'successful' => false,
                'message'    => 'Node not found',
            ], 404);
        }

        // شغّل الـ FPM أولاً ثم الـ web
        shell_exec("docker start {$this->nodes[$node]['fpm']} 2>/dev/null");
        sleep(2);
        shell_exec("docker start {$this->nodes[$node]['web']} 2>/dev/null");

        return response()->json([
            'successful' => true,
            'message'    => "{$node} started",
            'node'       => env('NODE_NAME', gethostname()),
        ]);
    }

    public function restoreAll(): JsonResponse
    {
        // شغّل كل الـ FPM أولاً
        foreach ($this->nodes as $containers) {
            shell_exec("docker start {$containers['fpm']} 2>/dev/null");
        }

        sleep(2);

        // ثم كل الـ web
        foreach ($this->nodes as $containers) {
            shell_exec("docker start {$containers['web']} 2>/dev/null");
        }

        return response()->json([
            'successful' => true,
            'message'    => 'All nodes restored',
            'node'       => env('NODE_NAME', gethostname()),
        ]);
    }
}
