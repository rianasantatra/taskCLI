#!/usr/bin/env php
<?php

class TaskTracker {
    private $tasksFile = 'tasks.json';
    
    public function __construct() {
        // Initialize tasks file if it doesn't exist
        if (!file_exists($this->tasksFile)) {
            file_put_contents($this->tasksFile, json_encode([]));
        }
    }
    
    private function loadTasks() {
        $tasksJson = file_get_contents($this->tasksFile);
        return json_decode($tasksJson, true) ?: [];
    }
    
    private function saveTasks($tasks) {
        file_put_contents($this->tasksFile, json_encode($tasks, JSON_PRETTY_PRINT));
    }
    
    private function displayTasks($tasks, $title = null) {
        if ($title) {
            echo "\n$title\n";
            echo str_repeat('-', strlen($title)) . "\n";
        }
        
        if (empty($tasks)) {
            echo "No tasks found.\n";
            return;
        }
        
        foreach ($tasks as $id => $task) {
            $status = $task['status'] ?? 'todo';
            $statusMap = [
                'todo' => 'To Do',
                'inprogress' => 'In Progress',
                'done' => 'Done'
            ];
            $displayStatus = $statusMap[$status] ?? $status;
            
            echo "[$id] {$task['title']} (Status: $displayStatus)";
            if (!empty($task['description'])) {
                echo "\n    Description: {$task['description']}";
            }
            echo "\n";
        }
        echo "\n";
    }
    
    public function run($args) {
        $command = $args[1] ?? null;
        
        try {
            switch ($command) {
                case 'add':
                    $this->addTask($args);
                    break;
                case 'update':
                    $this->updateTask($args);
                    break;
                case 'delete':
                    $this->deleteTask($args);
                    break;
                case 'progress':
                    $this->markTask($args, 'inprogress');
                    break;
                case 'done':
                    $this->markTask($args, 'done');
                    break;
                case 'list':
                    $this->listTasks($args);
                    break;
                case 'help':
                case null:
                    $this->showHelp();
                    break;
                default:
                    echo "Unknown command: $command\n";
                    $this->showHelp();
                    break;
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
    
    private function addTask($args) {
        if (count($args) < 3) {
            throw new Exception("Usage: task add \"<title>\" [\"<description>\"]");
        }
        
        $tasks = $this->loadTasks();
        $newId = uniqid();
        
        $tasks[$newId] = [
            'title' => $args[2],
            'description' => $args[3] ?? '',
            'status' => 'todo',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->saveTasks($tasks);
        echo "Task added with ID: $newId\n";
    }
    
    private function updateTask($args) {
        if (count($args) < 4) {
            throw new Exception("Usage: task update <id> \"<new title>\" [\"<new description>\"]");
        }
        
        $tasks = $this->loadTasks();
        $id = $args[2];
        
        if (!isset($tasks[$id])) {
            throw new Exception("Task with ID $id not found.");
        }
        
        $tasks[$id]['title'] = $args[3];
        if (isset($args[4])) {
            $tasks[$id]['description'] = $args[4];
        }
        
        $this->saveTasks($tasks);
        echo "Task updated successfully.\n";
    }
    
    private function deleteTask($args) {
        if (count($args) < 3) {
            throw new Exception("Usage: task delete <id>");
        }
        
        $tasks = $this->loadTasks();
        $id = $args[2];
        
        if (!isset($tasks[$id])) {
            throw new Exception("Task with ID $id not found.");
        }
        
        unset($tasks[$id]);
        $this->saveTasks($tasks);
        echo "Task deleted successfully.\n";
    }
    
    private function markTask($args, $status) {
        if (count($args) < 3) {
            throw new Exception("Usage: task $status <id>");
        }
        
        $tasks = $this->loadTasks();
        $id = $args[2];
        
        if (!isset($tasks[$id])) {
            throw new Exception("Task with ID $id not found.");
        }
        
        $tasks[$id]['status'] = $status;
        $this->saveTasks($tasks);
        echo "Task marked as $status.\n";
    }
    
    private function listTasks($args) {
        $filter = $args[2] ?? 'all';
        $tasks = $this->loadTasks();
        
        switch ($filter) {
            case 'all':
                $this->displayTasks($tasks, "All Tasks");
                break;
            case 'done':
                $doneTasks = array_filter($tasks, fn($t) => ($t['status'] ?? '') === 'done');
                $this->displayTasks($doneTasks, "Completed Tasks");
                break;
            case 'todo':
                $todoTasks = array_filter($tasks, fn($t) => ($t['status'] ?? '') === 'todo');
                $this->displayTasks($todoTasks, "Tasks To Do");
                break;
            case 'inprogress':
                $inProgressTasks = array_filter($tasks, fn($t) => ($t['status'] ?? '') === 'inprogress');
                $this->displayTasks($inProgressTasks, "Tasks In Progress");
                break;
            default:
                throw new Exception("Invalid filter. Use: all, done, todo, or inprogress");
        }
    }
    
    private function showHelp() {
        echo "Task Tracker CLI\n";
        echo "Usage: task <command> [arguments]\n\n";
        echo "Commands:\n";
        echo "  add \"<title>\" [\"<description>\"] - Add a new task\n";
        echo "  update <id> \"<new title>\" [\"<new description>\"] - Update a task\n";
        echo "  delete <id> - Delete a task\n";
        echo "  progress <id> - Mark task as in progress\n";
        echo "  done <id> - Mark task as done\n";
        echo "  list [all|done|todo|inprogress] - List tasks (default: all)\n";
        echo "  help - Show this help message\n";
    }
}

// Run the application
if (php_sapi_name() === 'cli') {
    $tracker = new TaskTracker();
    $tracker->run($argv);
} else {
    echo "This script is meant to be run from the command line.\n";
}