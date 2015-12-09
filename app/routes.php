<?php

$app->get('/random(/:count)', function($count = 1) use($app) {
	global $config, $bible_books;

	if (!preg_match('/^[0-9]+$/', $count))
		return $app->pass();

	$count = intval($count, 10);
	if ($count < 1 || $count > 10) {
		return $app->render(500, [
			'error' => 'Count must be between 1 and 10'
		]);
	}

	$sourceCounts = [];
	for ($i=0; $i < $count; $i++) {
		$source = array_rand($config['sources']);
		if (array_key_exists($source, $sourceCounts)) {
			$sourceCounts[$source]++;
		} else {
			$sourceCounts[$source] = 1;
		}
	}


	try {

		$db = new mysqli(
			$config['dbCredentials']['server'],
			$config['dbCredentials']['username'],
			$config['dbCredentials']['password'],
			$config['dbCredentials']['database_name']
		);
		$db->set_charset($config['dbCredentials']['charset']);
		$lines = [];

		foreach ($sourceCounts as $source => $sourceCount) {
			$sourceDb = $config['sources'][$source];

			$result = $db->query(
				'SELECT *' .
				' FROM `' . $sourceDb . '` ' .
				'WHERE `blacklisted` = 0 ' .
				//  Require minimum length of 60 characters
				// (this reduces the number of quran verses to just 30, while there are 30,000 bible verses to choose from)
				'AND LENGTH(text) >= 50 ' .
				// Require uppercase first letter and full stop at end
				// Disabled as this was wayyyy too limiting
				// 'AND SUBSTRING(text, 1, 1) COLLATE utf8_bin = UPPER(SUBSTRING(text, 1, 1)) ' .
				// 'AND SUBSTRING(text, -1) = "." ' .
				'ORDER BY RAND() LIMIT ' . $sourceCount
			);

			if (!$result->num_rows)
				throw new Exception("Could not get lines", 1);

			while ($line = $result->fetch_assoc()) {
				// Normalise data
				$line['source'] = $source;
				if ($source === 'bible') {
					$line['book'] = $bible_books[$line['book'] - 1];
				} else if ($source === 'quran') {
					$line['text'] = str_replace('´', '\'', $line['text']);
				}

				array_push($lines, $line);
			}
		}

		shuffle($lines);
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
