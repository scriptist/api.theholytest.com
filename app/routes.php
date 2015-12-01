<?php

$app->get('/random(/:count)', function($count = 1) use($app) {
	if (!preg_match('/^[0-9]+$/', $count) || intval($count) < 1 || intval($count) > 10)
		return $app->pass();

	$count = intval($count);
	if ($count < 1 || $count > 10) {
		return $app->render(500, [
			'error' => 'Count must be between 1 and 10'
		]);
	}

	global $config, $bible_books;
	$book = array_rand($config['books']);
	$bookDb = $config['books'][$book];

	try {

		$db = new mysqli(
			$config['dbCredentials']['server'],
			$config['dbCredentials']['username'],
			$config['dbCredentials']['password'],
			$config['dbCredentials']['database_name']
		);
		$db->set_charset($config['dbCredentials']['charset']);
		$result = $db->query(
			'SELECT *' .
			' FROM `' . $bookDb . '` ' .
			'WHERE `blacklisted` = 0 ' .
			//  Require minimum length of 60 characters
			'AND LENGTH(text) >= 60 ' .
			// Require uppercase first letter and full stop at end
			// 'AND SUBSTRING(text, 1, 1) COLLATE utf8_bin = UPPER(SUBSTRING(text, 1, 1)) ' .
			// 'AND SUBSTRING(text, -1) = "." ' .
			'ORDER BY RAND() LIMIT ' . $count
		);

		if (!$result->num_rows)
			throw new Exception("Could not get lines", 1);

		$lines = [];

		while ($line = $result->fetch_assoc()) {
			array_push($lines, $line);

			// Normalise data
			if ($book === 'bible') {
				$line['book'] = $bible_books[$line['book']];
			} else if ($book === 'quran') {
				$line['text'] = str_replace('´', '\'', $line['text']);
			}
		}
	} catch (Exception $e) {
		return $app->render(500, [
			'error' => $e->getMessage()
		]);
	}

	$app->render(200, [
		'lines' => $lines,
		'book' => $book,
	]);
});

?>
