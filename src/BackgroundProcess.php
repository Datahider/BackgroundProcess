<?php

namespace losthost\BackgroundProcess;

class BackgroundProcess {

    protected string $php_template;
    
    public function __construct(string $php_template) {
        $this->php_template = $php_template;
    }
    
    static public function create(string $php_template) : static {
        return new static($php_template);
    }
    
    public function run(...$params) : void {
        $escaped_params = array_map(fn($p) => var_export($p, true), $params);
        $code = sprintf($this->php_template, ...$escaped_params);

        $descriptorspec = [
            0 => ['pipe', 'r'], // stdin - передаем код
        ];
        $pipes = [];
        
        $php = '"' . PHP_BINARY . '"';
        $process = proc_open($php, $descriptorspec, $pipes);
        
        if (is_resource($process)) {
            if (!isset($pipes[0])) {
                throw new \RuntimeException('Failed to create stdin pipe');
            }
            fwrite($pipes[0], $code);
            fclose($pipes[0]);
        }
    }
}
