<?php

use Foolz\Foolframe\Model\DoctrineConnection as DC;

\Foolz\Plugin\Event::forge('Foolz\Plugin\Plugin::execute.foolz/foolfuuka-plugin-board-statistics')
	->setCall(function($result) {
		\Autoloader::add_classes(array(
			'Foolz\Foolfuuka\Plugins\BoardStatistics\Model\BoardStatistics' => __DIR__.'/classes/model/board_statistics.php',
			'Foolz\Foolframe\Controller\Admin\Plugins\BoardStatistics' => __DIR__.'/classes/controller/admin.php',
			'Foolz\Foolfuuka\Controller\Chan\BoardStatistics' => __DIR__.'/classes/controller/chan.php',
			'Foolz\Foolfuuka\Plugins\BoardStatistics\Model\Task' => __DIR__.'/classes/tasks/task.php'
		));

		// don't add the admin panels if the user is not an admin
		if (\Auth::has_access('maccess.admin'))
		{
			\Plugins::register_sidebar_element('admin', 'plugins', array(
				"content" => array("board_statistics/manage" => array("level" => "admin", "name" => __("Board Statistics"), "icon" => 'icon-bar-chart'))
			));

			\Foolz\Plugin\Event::forge('ff.task.fool.run.sections.alter')
				->setCall(function($result){
					$array = $result->getParam('array');
					$array[] = 'board_statistics';
					$result->set($array);
					$result->setParam('array', $array);
				});

			\Foolz\Plugin\Event::forge('ff.task.fool.run.sections.call_help.board_statistics')
				->setCall('Foolz\Foolfuuka\Plugins\BoardStatistics\Model\Task::cli_board_statistics_help');

			\Foolz\Plugin\Event::forge('ff.task.fool.run.sections.call.board_statistics')
				->setCall('Foolz\Foolfuuka\Plugins\BoardStatistics\Model\Task::cli_board_statistics');
		}

		\Foolz\Plugin\Event::forge('ff.themes.generic_top_nav_buttons')
			->setCall(function($result){
				$top_nav = $result->getParam('nav');
				if(\Radix::getSelected())
				{
					$top_nav[] = array('href' => Uri::create(array(Radix::getSelected()->shortname, 'statistics')), 'text' => __('Stats'));
					$result->set($top_nav);
					$result->setParam('nav', $top_nav);
				}
			})->setPriority(3);

		\Foolz\Plugin\Event::forge('Fuel\Core\Router.parse_match.intercept')
			->setCall(function($result)
			{
				if ($result->getParam('controller') === 'Foolz\Foolfuuka\Controller\Chan')
				{
					$method_params = $result->getParam('method_params');

					if (isset($method_params[0]) && $method_params[0] === 'statistics')
					{
						$result->setParam('controller', 'Foolz\Foolfuuka\Controller\Chan\BoardStatistics');
						$result->set(true);
					}
				}
			})->setPriority(4);
	});

\Foolz\Plugin\Event::forge('Foolz\Foolframe\Model\Plugin::schemaUpdate.foolz/foolfuuka-plugin-board-statistics')
	->setCall(function($result) {
		/* @var $schema \Doctrine\DBAL\Schema\Schema */
		/* @var $table \Doctrine\DBAL\Schema\Table */
		$schema = $result->getParam('schema');
		$table = $schema->createTable(DC::p('plugin_fu_board_statistics'));
		if (DC::forge()->getDriver()->getName() == 'pdo_mysql')
		{
			$table->addOption('charset', 'utf8mb4');
			$table->addOption('collate', 'utf8mb4_unicode_ci');
		}
		$table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
		$table->addColumn('board_id', 'integer', ['unsigned' => true]);
		$table->addColumn('name', 'string', ['length' => 32]);
		$table->addColumn('timestamp', 'integer', ['unsigned' => true]);
		$table->addColumn('data', 'text', ['length' => 65532]);
		$table->setPrimaryKey(['id']);
		$table->addUniqueIndex(['board_id', 'name'], 'board_id_name_index');
		$table->addIndex(['timestamp'], 'timestamp_index');
	});