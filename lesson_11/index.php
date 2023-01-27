<?
require_once (__DIR__.'/crest.php');

$result = CRest::call(
		'tasks.task.add',
		[
			'fields' => [
				'TITLE' => 'Изучить пример 11 видео-курса',
				'DESCRIPTION' => 'Просмотреть видео, скачать пример.
				
				Заменить код вебхука и выложить на сервер для выполнения
				',
				'DEADLINE' => date('Y-m-d', time() + 3600 * 24 * 7).' 19:00',
				'RESPONSIBLE_ID' => 1,

				// 'AUDITORS' => [2, 3],
				// 'ALLOW_CHANGE_DEADLINE' => 0,
				// 'TASK_CONTROL' => 1,
				],
		]
	);

echo '<pre>';
	print_r($result);
echo '</pre>';