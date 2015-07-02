<?php
/**
 * Hook for cmd map processing
 *
 * @package Calendarize\Hooks
 * @author  Tim Lochmüller
 */

namespace HDNET\Calendarize\Hooks;

use HDNET\Calendarize\Register;
use HDNET\Calendarize\Utility\HelperUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * Hook for cmd map processing
 *
 * @author Tim Lochmüller
 * @hook   TYPO3_CONF_VARS|SC_OPTIONS|t3lib/class.t3lib_tcemain.php|processCmdmapClass
 */
class ProcessCmdmapClass {

	/**
	 * @param             $table
	 * @param             $id
	 * @param             $recordToDelete
	 * @param             $recordWasDeleted
	 * @param DataHandler $dataHandler
	 */
	public function processCmdmap_deleteAction($table, $id, $recordToDelete, &$recordWasDeleted, DataHandler $dataHandler) {
		$register = Register::getRegister();
		foreach ($register as $key => $configuration) {
			if ($configuration['tableName'] == $table) {
				$indexer = HelperUtility::create('HDNET\\Calendarize\\Service\\IndexerService');
				$dataHandler->deleteEl($table, $id);
				$recordWasDeleted = TRUE;
				$indexer->reindex($key, $table, $id);
			}
		}
	}
}