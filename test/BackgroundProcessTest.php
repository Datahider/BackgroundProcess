<?php

namespace losthost\BackgroundProcess\Test;

use PHPUnit\Framework\TestCase;
use losthost\BackgroundProcess\BackgroundProcess;

class BackgroundProcessTest extends TestCase {
    
    protected $test_file;
    
    protected function setUp(): void {
        $this->test_file = sys_get_temp_dir() . '/bg_process_test.txt';
        if (file_exists($this->test_file)) {
            unlink($this->test_file);
        }
    }
    
    public function testBasicExecution() {
        $process = BackgroundProcess::create(<<<'PHP'
            <?php
            file_put_contents(%s, 'Hello World');
            PHP);
        $process->run($this->test_file);
        
        sleep(1); // Даем время выполниться
        $this->assertFileExists($this->test_file);
        $this->assertEquals('Hello World', file_get_contents($this->test_file));
    }
    
    public function testParameterEscaping() {
        $dangerous_string = "Hello'); evil_code(); //";
        
        $process = BackgroundProcess::create(<<<'PHP'
            <?php
            file_put_contents(%s, %s);
            PHP);
        $process->run($this->test_file, $dangerous_string);
        
        sleep(1);
        $content = file_get_contents($this->test_file);
        $this->assertEquals($dangerous_string, $content);
    }
    
    public function testMultipleParameters() {
        $process = BackgroundProcess::create(<<<'PHP'
            <?php
            file_put_contents(%s, %s . ' ' . %s);
            PHP);
        $process->run($this->test_file, 'Hello', 'World');
        
        sleep(1);
        $this->assertEquals('Hello World', file_get_contents($this->test_file));
    }
    
    public function testArrayParameter() {
        $data = ['a' => 1, 'b' => 2];
        
        $process = BackgroundProcess::create(<<<'PHP'
            <?php
            $data = %s;
            file_put_contents(%s, $data['a'] + $data['b']);
            PHP);
        $process->run($data, $this->test_file);
        
        sleep(1);
        $this->assertEquals('3', file_get_contents($this->test_file));
    }
    
    public function testStaticCreateMethod() {
        $process = BackgroundProcess::create('<?php file_put_contents(%s, "test");');
        $this->assertInstanceOf(BackgroundProcess::class, $process);
        
        $process->run($this->test_file);
        sleep(1);
        $this->assertEquals('test', file_get_contents($this->test_file));
    }
    
    public function testOutputToNowhere() {
        // Тест что вывод в stdout/stderr не ломает процесс
        $process = BackgroundProcess::create(<<<'PHP'
            <?php
            echo "This goes to nowhere";
            file_put_contents("php://stderr", "This too");
            error_log("And this");
            file_put_contents(%s, "But this works");
            PHP);
        $process->run($this->test_file);

        sleep(1);
        // Процесс должен завершиться нормально несмотря на вывод в закрытые дескрипторы
        $this->assertFileExists($this->test_file);
        $this->assertEquals('But this works', file_get_contents($this->test_file));
    }
    
    public function testKill(): void {
        $code = <<<'PHP'
            <?php
            while (true) {
                error_log("Background process running at " . date('H:i:s'));
                sleep(1);
            }
            PHP;

        $process = new BackgroundProcess($code);
        $process->run();

        // Даём процессу немного поработать
        sleep(3);

        // Убиваем
        $this->assertTrue($process->kill());

        // Проверяем что процесс действительно умер
        sleep(1);
        
        $is_running = $process->isRunning();
        $this->expectException(\RuntimeException::class);
    }
    
    
    protected function tearDown(): void {
        if (file_exists($this->test_file)) {
            unlink($this->test_file);
        }
    }
}
