<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NodeController extends Controller
{
    private array $nodes = [
        'Node-1' => 'app-node-1',
        'Node-2' => 'app-node-2',
        'Node-3' => 'app-node-3',
    ];

    public function status(): JsonResponse
    {
        $result = [];

        foreach ($this->nodes as $nodeName => $container) {

            $status = trim(shell_exec(
                "docker inspect -f '{{.State.Running}}' {$container} 2>/dev/null"
            ));

            $result[$nodeName] = [
                'container' => $container,
                'running' => $status === 'true',
            ];
        }

        return response()->json([
            'successful' => true,
            'nodes' => $result,
        ]);
    }

    public function stop(string $node): JsonResponse
    {
        if (!isset($this->nodes[$node])) {
            return response()->json([
                'successful' => false,
                'message' => 'Invalid node',
            ], 404);
        }

        $container = $this->nodes[$node];

        shell_exec("docker stop {$container}");

        return response()->json([
            'successful' => true,
            'message' => "{$node} stopped successfully",
        ]);
    }

    public function start(string $node): JsonResponse
    {
        if (!isset($this->nodes[$node])) {
            return response()->json([
                'successful' => false,
                'message' => 'Invalid node',
            ], 404);
        }

        $container = $this->nodes[$node];

        shell_exec("docker start {$container}");

        return response()->json([
            'successful' => true,
            'message' => "{$node} started successfully",
        ]);
    }

    public function restoreAll(): JsonResponse
    {
        foreach ($this->nodes as $container) {
            shell_exec("docker start {$container}");
        }

        return response()->json([
            'successful' => true,
            'message' => 'All nodes restored',
        ]);
    }
}
