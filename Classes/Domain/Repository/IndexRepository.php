<?php
/**
 * Index repository
 *
 * @author  Tim Lochmüller
 */

namespace HDNET\Calendarize\Domain\Repository;

use HDNET\Calendarize\Domain\Model\Index;
use HDNET\Calendarize\Utility\DateTimeUtility;
use HDNET\Calendarize\Utility\HelperUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * Index repository
 */
class IndexRepository extends AbstractRepository
{

    /**
     * Default orderings for index records
     *
     * @var array
     */
    protected $defaultOrderings = [
        'start_date' => QueryInterface::ORDER_ASCENDING,
        'start_time' => QueryInterface::ORDER_ASCENDING,
    ];

    /**
     * Index types for selection
     *
     * @var array
     */
    protected $indexTypes = [];

    /**
     * Set the index types
     *
     * @param array $types
     */
    public function setIndexTypes(array $types)
    {
        $this->indexTypes = $types;
    }

    /**
     * Current content record
     *
     * @var array
     */
    protected $contentRecord = [];

    /**
     * Set the current content record
     *
     * @param array $contentRecord
     */
    public function setContentRecord($contentRecord)
    {
        $this->contentRecord = $contentRecord;
    }

    /**
     * Find List
     *
     * @param int $limit
     *
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findList($limit = 0)
    {
        $query = $this->createQuery();
        $constraints = $this->getDefaultConstraints($query);

        // time check
        $orConstraint = [];
        $orConstraint[] = $query->greaterThanOrEqual('start_date', $GLOBALS['SIM_ACCESS_TIME']);
        $orConstraint[] = $query->logicalAnd([
            $query->lessThanOrEqual('start_date', $GLOBALS['SIM_ACCESS_TIME']),
            $query->greaterThanOrEqual('end_date', $GLOBALS['SIM_ACCESS_TIME'])
        ]);

        $constraints[] = $query->logicalOr($orConstraint);

        if ($limit > 0) {
            $query->setLimit($limit);
        }

        return $this->matchAndExecute($query, $constraints);
    }

    /**
     * Find by custom search
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param array     $customSearch
     *
     * @return array
     */
    public function findBySearch(\DateTime $startDate = null, \DateTime $endDate = null, array $customSearch = [])
    {
        $arguments = [
            'indexIds'     => [],
            'startDate'    => $startDate,
            'endDate'      => $endDate,
            'customSearch' => $customSearch,
        ];
        $signalSlotDispatcher = HelperUtility::getSignalSlotDispatcher();
        $arguments = $signalSlotDispatcher->dispatch(__CLASS__, __FUNCTION__ . 'Pre', $arguments);

        $query = $this->createQuery();
        $constraints = $this->getDefaultConstraints($query);
        if ($arguments['startDate'] instanceof \DateTime) {
            $constraints[] = $query->greaterThan('start_date', $arguments['startDate']);
        }
        if ($arguments['endDate'] instanceof \DateTime) {
            $constraints[] = $query->lessThan('start_date', $arguments['endDate']);
        }
        if ($arguments['indexIds']) {
            $constraints[] = $query->in('foreign_uid', $arguments['indexIds']);
        }
        $result = [
            'result' => $this->matchAndExecute($query, $constraints)
        ];
        $signalSlotDispatcher->dispatch(__CLASS__, __FUNCTION__ . 'Post', $result);

        return $result['result'];
    }

    /**
     * Find by traversing information
     *
     * @param Index      $index
     * @param bool|true  $future
     * @param bool|false $past
     * @param int        $limit
     * @param string     $sort
     *
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findByTraversing(
        Index $index,
        $future = true,
        $past = false,
        $limit = 100,
        $sort = QueryInterface::ORDER_ASCENDING
    ) {
        if (!$future && !$past) {
            return [];
        }
        $query = $this->createQuery();
        $constraints = [];
        $constraints[] = $query->logicalNot($query->equals('uid', $index->getUid()));
        $constraints[] = $query->equals('foreignTable', $index->getForeignTable());
        $constraints[] = $query->equals('foreignUid', $index->getForeignUid());
        if (!$future) {
            $constraints[] = $query->lessThanOrEqual('startDate', $GLOBALS['SIM_ACCESS_TIME']);
        }
        if (!$past) {
            $constraints[] = $query->greaterThanOrEqual('startDate', $GLOBALS['SIM_ACCESS_TIME']);
        }

        $query->setLimit($limit);
        $sort = $sort === QueryInterface::ORDER_ASCENDING ? QueryInterface::ORDER_ASCENDING : QueryInterface::ORDER_DESCENDING;
        $query->setOrderings($this->getSorting($sort));
        return $this->matchAndExecute($query, $constraints);
    }

    /**
     * find Year
     *
     * @param int $year
     *
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findYear($year)
    {
        $query = $this->createQuery();
        $constraints = $this->getDefaultConstraints($query);
        $this->addTimeFrameConstraints($constraints, $query, mktime(0, 0, 0, 1, 1, $year), mktime(0, 0, 0, 1, 1, $year + 1));
        return $this->matchAndExecute($query, $constraints);
    }

    /**
     * find Month
     *
     * @param int $year
     * @param int $month
     *
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findMonth($year, $month)
    {
        $query = $this->createQuery();
        $constraints = $this->getDefaultConstraints($query);
        $startTime = mktime(0, 0, 0, $month, 1, $year);
        $endTime = mktime(0, 0, 0, $month + 1, 1, $year);
        $this->addTimeFrameConstraints($constraints, $query, $startTime, $endTime);
        return $this->matchAndExecute($query, $constraints);
    }

    /**
     * find Week
     *
     * @param int $year
     * @param int $week
     *
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findWeek($year, $week)
    {
        $query = $this->createQuery();
        $constraints = $this->getDefaultConstraints($query);

        $firstDay = DateTimeUtility::convertWeekYear2DayMonthYear($week, $year);
        $timeStampStart = $firstDay->getTimestamp();
        $this->addTimeFrameConstraints($constraints, $query, $timeStampStart, $timeStampStart + DateTimeUtility::SECONDS_WEEK);
        return $this->matchAndExecute($query, $constraints);
    }

    /**
     * find day
     *
     * @param int $year
     * @param int $month
     * @param int $day
     *
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findDay($year, $month, $day)
    {
        $query = $this->createQuery();
        $constraints = $this->getDefaultConstraints($query);
        $startTime = mktime(0, 0, 0, $month, $day, $year);
        $this->addTimeFrameConstraints($constraints, $query, $startTime, $startTime + DateTimeUtility::SECONDS_DAY);
        return $this->matchAndExecute($query, $constraints);
    }

    /**
     * Get the default constraint for the queries
     *
     * @param QueryInterface $query
     *
     * @return array
     */
    protected function getDefaultConstraints(QueryInterface $query)
    {
        $constraints = [];
        $constraints[] = $query->in('uniqueRegisterKey', $this->indexTypes);

        // storage page selection
        // @todo please check core API functions again
        /** @var ConfigurationManagerInterface $configuratioManager */
        $configurationManager = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface');
        $frameworkConfiguration = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        $storagePages = isset($frameworkConfiguration['persistence']['storagePid']) ? GeneralUtility::intExplode(',',
            $frameworkConfiguration['persistence']['storagePid']) : [];
        if (!empty($storagePages)) {
            $constraints[] = $query->in('pid', $storagePages);
        }

        $arguments = [
            'indexIds'      => [],
            'indexTypes'    => $this->indexTypes,
            'contentRecord' => $this->contentRecord,
        ];
        $signalSlotDispatcher = HelperUtility::getSignalSlotDispatcher();
        $arguments = $signalSlotDispatcher->dispatch(__CLASS__, __FUNCTION__, $arguments);

        if ($arguments['indexIds']) {
            $constraints[] = $query->in('foreign_uid', $arguments['indexIds']);
        }

        return $constraints;
    }

    /**
     * Add time frame related queries
     *
     * @param array          $constraints
     * @param QueryInterface $query
     * @param int            $startTime
     * @param int            $endTime
     */
    protected function addTimeFrameConstraints(&$constraints, QueryInterface $query, $startTime, $endTime)
    {
        $orConstraint = [];

        // before - in
        $beforeIn = [
            $query->lessThan('start_date', $startTime),
            $query->greaterThanOrEqual('end_date', $startTime),
            $query->lessThan('end_date', $endTime),
        ];
        $orConstraint[] = $query->logicalAnd($beforeIn);

        // in - in
        $inIn = [
            $query->greaterThanOrEqual('start_date', $startTime),
            $query->lessThan('end_date', $endTime),
        ];
        $orConstraint[] = $query->logicalAnd($inIn);

        // in - after
        $inAfter = [
            $query->greaterThanOrEqual('start_date', $startTime),
            $query->lessThan('start_date', $endTime),
            $query->greaterThanOrEqual('end_date', $endTime),
        ];
        $orConstraint[] = $query->logicalAnd($inAfter);

        // before - after
        $beforeAfter = [
            $query->lessThan('start_date', $startTime),
            $query->greaterThan('end_date', $endTime),

        ];
        $orConstraint[] = $query->logicalAnd($beforeAfter);

        // finish
        $constraints[] = $query->logicalOr($orConstraint);
    }

    /**
     * Set the default sorting direction
     *
     * @param string $direction
     */
    public function setDefaultSortingDirection($direction)
    {
        $this->defaultOrderings = $this->getSorting($direction);
    }

    /**
     * Get the sorting
     *
     * @param string $direction
     *
     * @return array
     */
    protected function getSorting($direction)
    {
        return [
            'start_date' => $direction,
            'start_time' => $direction,
        ];
    }
}