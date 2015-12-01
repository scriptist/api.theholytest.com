<?php

$app->get('/random', function() use($app) {
	global $config;
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
			'SELECT * FROM `' . $bookDb . '` ' .
			'WHERE `blacklisted` = 0 ' .
			//  Require minimum length of 60 characters
			'AND LENGTH(text) >= 60 ' .
			// Require uppercase first letter and full stop at end
			// 'AND SUBSTRING(text, 1, 1) COLLATE utf8_bin = UPPER(SUBSTRING(text, 1, 1)) ' .
			// 'AND SUBSTRING(text, -1) = "." ' .
			'ORDER BY RAND() LIMIT 1'
		);

		$row = $result->fetch_assoc();
		$row['text'] = str_replace('´', '\'', $row['text']);
	} catch (Exception $e) {
		return $app->render(500, [
			'msg' => 'Error',
			'exception' => $e
		]);
	}

	$app->render(200, [
		'text' => $row['text'],
		'book' => $book,
	]);
});

?>
