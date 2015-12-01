<?php

$app->get('/random(/:count)', function($count = 1) use($app) {
	if (!preg_match('/^[0-9]+$/', $count))
		return $app->pass();

	$count = intval($count, 10);
	if ($count < 1 || $count > 10) {
		return $app->render(500, [
			'error' => 'Count must be between 1 and 10'
		]);
	}

	global $config, $bible_books;

	try {

		$db = new mysqli(
			$config['dbCredentials']['server'],
			$config['dbCredentials']['username'],
			$config['dbCredentials']['password'],
			$config['dbCredentials']['database_name']
		);
		$db->set_charset($config['dbCredentials']['charset']);
		$lines = [];

		for ($i=0; $i < $count; $i++) {
			$source = array_rand($config['books']);
			$sourceDb = $config['books'][$source];

			$result = $db->query(
				'SELECT *' .
				' FROM `' . $sourceDb . '` ' .
				'WHERE `blacklisted` = 0 ' .
				//  Require minimum length of 60 characters
				'AND LENGTH(text) >= 60 ' .
				// Require uppercase first letter and full stop at end
				// 'AND SUBSTRING(text, 1, 1) COLLATE utf8_bin = UPPER(SUBSTRING(text, 1, 1)) ' .
				// 'AND SUBSTRING(text, -1) = "." ' .
				'ORDER BY RAND() LIMIT 1'
			);

			if (!$result->num_rows)
				throw new Exception("Could not get lines", 1);


			$line = $result->fetch_assoc();

			// Normalise data
			$line['source'] = $source;
			if ($source === 'bible') {
				$line['book'] = $bible_books[$line['book']];
			} else if ($source === 'quran') {
				$line['text'] = str_replace('´', '\'', $line['text']);
			}

			array_push($lines, $line);
		}
	} catch (Exception $e) {
		return $app->render(500, [
			'error' => $e->getMessage(),
		]);
	}

	$app->render(200, [
		'lines' => $lines,
	]);
});

$app->post('/vote/:book/:id/:vote', function($book, $id, $vote) use($app) {
	global $config;
	if (!preg_match('/^[0-9]+$/', $id) || !array_key_exists($book, $config['books']) || !array_key_exists($vote, $config['books']))
		return $app->pass();
	$id = intval($id, 10);

	$bookDb = $config['books'][$book];
	$db = new mysqli(
		$config['dbCredentials']['server'],
		$config['dbCredentials']['username'],
		$config['dbCredentials']['password'],
		$config['dbCredentials']['database_name']
	);
	$db->set_charset($config['dbCredentials']['charset']);
	$result = $db->query(
		'UPDATE `' . $bookDb . '` ' .
		'SET `votes_' . $vote . '` = `votes_' . $vote . '` + 1 ' .
		'WHERE `id` = ' . $id
	);

	$app->render(200);
});

?>
