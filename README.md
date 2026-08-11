# PHP File Download

Библиотека для организации скачивания файлов через PHP: из файла на диске, из уже открытого
файлового указателя или из строки в памяти.

## Когда это нужно

Если файл доступен напрямую — отдавайте его встроенными средствами веб-сервера (например,
`X-Accel-Redirect` или `Alias`). Этот класс предназначен для случаев, когда файл создаётся
«на лету» в PHP и его нельзя отдать статически: временные файлы, сгенерированные PDF,
экспорт в память и т.п.

## Установка

```bash
composer require karelwintersky/arris.php-file-download
```

## Использование

Подключение класса:

```php
use Arris\Toolkit\FileDownload;
```

### Скачивание файла с диска

```php
$fileDownload = FileDownload::createFromFilePath("/path/to/file.pdf");
$fileDownload->sendDownload("download.pdf");
```

### Скачивание через файловый указатель

```php
$file = fopen("/path/to/file.pdf", "rb"); // либо tmpfile(), php://memory и т.п.
$fileDownload = FileDownload::createFromResource($file);
$fileDownload->sendDownload("download.pdf");
```

Если у вас уже есть и указатель, и путь к исходному файлу — можно воспользоваться
конструктором напрямую (имя файла будет взято из пути):

```php
$fileDownload = new FileDownload($file, "/path/to/file.pdf");
```

### Скачивание из строки

```php
$content = "содержимое файла";
$fileDownload = FileDownload::createFromString($content);
$fileDownload->sendDownload("download.txt");
```

Например, так можно отдать PDF, сгенерированный любой библиотекой:

```php
$pdf = new Zend_Pdf();
$page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
$pdf->pages[] = $page;

/* рисуем содержимое PDF ... */

$fileDownload = FileDownload::createFromString($pdf->render());
$fileDownload->sendDownload("download.pdf");
```

## Параметры sendDownload()

```php
public function sendDownload(string $filename = '', bool $forceDownload = true, bool $exit = true)
```

- `$filename` — имя, под которым сохранится файл у пользователя. Если опущено — берётся имя
  из пути, переданного конструктору/фабрике. В заголовке `Content-Disposition` имя отдаётся
  в двух видах: санитизированный ASCII-вариант (`filename=`) и закодированный
  (`filename*=UTF-8''...`, RFC 5987), поэтому кириллические имена сохраняются корректно.
- `$forceDownload = false` — файл откроется в браузере (`inline`), а не будет скачан
  (`attachment`).
- `$exit = true` — после отправки файла выполнение скрипта завершается, чтобы лишний вывод
  не повредил ответ. Для случаев, когда нужно продолжить работу после отправки, передайте
  `false`.

Полный пример:

```php
$fileDownload = FileDownload::createFromFilePath("/path/to/report.pdf");

// скачать как «Отчёт.pdf», не завершая скрипт
$fileDownload->sendDownload("Отчёт.pdf", true, false);
```

## Примечания

- Если в `sendDownload()` опустить имя файла, оно будет равно имени файла из пути,
  переданного конструктору.
- Для файла, созданного через `createFromString()`, имя в конструкторе не задаётся — без
  аргумента в `sendDownload()` файл будет скачан с пустым именем. Всегда передавайте имя
  явно.
- Неизвестные расширения файлов отдаются как `application/octet-stream`.
- Объект закрывает файловый указатель в деструкторе (в том числе временный файл из
  `createFromString()`).
- `sendDownload()` бросает `RuntimeException`, если заголовки уже были отправлены.
