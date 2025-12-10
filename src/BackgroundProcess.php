<?php

namespace losthost\BackgroundProcess;

class BackgroundProcess {

    /** @var resource|null */
    protected $process;
    protected $uniqid;

    protected array $pipes = [];
    protected string $php_template;
    protected int $exit_code;


    public function __construct(string $php_template) {
        $this->php_template = $php_template;
        $this->uniqid = uniqid();
        DEBUG && error_log("BackgroundProcess $this->uniqid: Created with template:\n\n$php_template\n\nStack trace:\n\n". debug_backtrace());
    }
    
    static public function create(string $php_template) : static {
        return new static($php_template);
    }
    
    public function run(...$params) : static {
        $escaped_params = array_map(fn($p) => var_export($p, true), $params);
        DEBUG && error_log("BackgroundProcess $this->uniqid: Started with params:\n\n". print_r($escaped_params, true));
        $code = sprintf($this->php_template, ...$escaped_params);

        $descriptorspec = [
            0 => ['pipe', 'r'], // stdin - передаем код
        ];
        $pipes = [];
        
        $php = '"' . PHP_BINARY . '"';
        $process = proc_open($php, $descriptorspec, $pipes);
        
        if (is_resource($process) && isset($pipes[0])) {
            fwrite($pipes[0], $code);
            fclose($pipes[0]);
            $this->pipes = $pipes;
            $this->process = $process;
            return $this;
        }

        throw new \RuntimeException('Failed to create stdin pipe');
    }
        
    public function kill(): bool {
        if (isset($this->process)) {
            $pid = $this->getPid();
            DEBUG && error_log("BackgroundProcess $this->uniqid: Killing pid $pid");
            $ok = stripos(php_uname('s'), 'win')>-1  
                    ? exec("taskkill /F /T /PID $pid") 
                    : exec("kill -9 $pid");
            if ($ok !== false && !$this->isRunning()) {
                DEBUG && error_log("BackgroundProcess $this->uniqid: Killed pid $pid");
                $this->process = null;
                return true;
            }
        }
        throw new \RuntimeException("Can't teminate the process.");
    }

    public function isRunning(): bool {
        if (!is_resource($this->process)) {
            throw new \RuntimeException('The process is already terminated or was never starded.');
        }

        $status = proc_get_status($this->process);
        return $status['running'];
    }

    public function getPid(): ?int {
        if (!is_resource($this->process)) {
            throw new \RuntimeException('The process is already terminated or was never starded.');
        }

        $status = proc_get_status($this->process);
        return $status['pid'] ?? null;
    }
    
    public function readOutput(): string {
        return stream_get_contents($this->pipes[1] ?? null);
    }
        
}
