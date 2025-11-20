# BackgroundProcess

Легкая и безопасная библиотека для запуска PHP кода в фоновых процессах.

## Особенности

- 🚀 **Простой API** - всего 2 метода для запуска
- 🔒 **Безопасность** - автоматическое экранирование параметров
- 💻 **Кроссплатформенность** - работает на Windows и *nix
- 🧩 **Гибкость** - любой PHP код в фоновом процессе
- 🧪 **Протестировано** - полное покрытие PHPUnit
- 🔇 **Тихий режим** - stdout/stderr автоматически подавляются

## Установка

```bash
composer require losthost/background-process
```

## Быстрый старт

```php
use losthost\BackgroundProcess\BackgroundProcess;

// Простой пример
$process = BackgroundProcess::create('<?php file_put_contents(%s, "Hello World");');
$process->run('/tmp/output.txt');

// С параметрами
$process = BackgroundProcess::create('<?php file_put_contents(%s, "User: " . %s);');
$process->run('/tmp/user.txt', 'john_doe');
```

## Использование

### Базовое использование

```php
$process = new BackgroundProcess('<?php file_put_contents("log.txt", "Task completed");');
$process->run();
```

### С фабричным методом

```php
$process = BackgroundProcess::create('<?php (new %s())->process(%s);');
$process->run(MyWorker::class, $data);
```

### Сложный пример с инициализацией

```php
$code = <<<'PHP'
        <?php
        require 'vendor/autoload.php';
        require 'config.php';

        // Инициализация
        DB::connect(%s, %s, %s, %s, %s);

        // Работа
        $result = (new %s())->heavyProcessing(%s);

        // Сохранение результата
        file_put_contents(%s, $result);
        PHP;

$process = BackgroundProcess::create($code);
$process->run(
    $db_host, $db_user, $db_pass, $db_name, $prefix,
    MyWorker::class, $large_dataset, $output_file
);
```

## Безопасность

Библиотека автоматически экранирует все параметры через `var_export()`:

```php
// Опасные строки становятся безопасными
$process->run("hello'); system('rm -rf /'); //");
// Преобразуется в: 'hello\'); system(\'rm -rf /\'); //'
// И выполняется как строка, а не код
```

## Особенности работы

- **Процессы запускаются асинхронно** - родительский процесс не ждет завершения
- **Stdout/stderr подавляются** - весь вывод идет в /dev/null (nix) или NUL (windows)
- **Для результатов используйте файлы** или другие механизмы межпроцессного взаимодействия

## Примеры использования

### Обработка очереди сообщений

```php
$process = BackgroundProcess::create('<?php
    require "vendor/autoload.php";
    $worker = new MessageWorker();
    $worker->processUser(%d);
');
$process->run($user_id);
```

### Генерация отчетов

```php
$process = BackgroundProcess::create('<?php
    require "vendor/autoload.php";
    $report = new ReportGenerator();
    $pdf = $report->generate(%s, %s);
    file_put_contents(%s, $pdf);
');
$process->run($report_type, $date_range, $output_file);
```

### Параллельная обработка данных

```php
foreach ($users as $user) {
    $process = BackgroundProcess::create('<?php
        require "vendor/autoload.php";
        (new DataProcessor())->analyzeUser(%s);
    ');
    $process->run($user->id);
}
```

## Требования

- PHP 8.0+
- Расширение proc_open

## Лицензия

MIT
