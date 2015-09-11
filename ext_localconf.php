<?php
/**
 * General ext_localconf file and also an example for your own extension
 *
 * @category Extension
 * @package  Calendarize
 * @author   Tim Lochmüller
 */

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

\HDNET\Autoloader\Loader::extLocalconf('HDNET', 'calendarize', \HDNET\Calendarize\Register::getDefaultAutoloader());

if (!(boolean)\HDNET\Calendarize\Utility\ConfigurationUtility::get('disableDefaultEvent')) {
    \HDNET\Calendarize\Register::extLocalconf(\HDNET\Calendarize\Register::getDefaultCalendarizeConfiguration());
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin('HDNET.calendarize', 'Calendar',
    ['Calendar' => 'list,latest,year,month,week,day,detail,search'], ['Calendar' => 'list,year,month,week,day,detail,search']);