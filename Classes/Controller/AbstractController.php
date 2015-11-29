<?php
/**
 * Abstract controller
 *
 * @author  Tim Lochmüller
 */

namespace HDNET\Calendarize\Controller;

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * Abstract controller
 */
abstract class AbstractController extends ActionController
{

    /**
     * Init all actions
     */
    public function initializeAction()
    {
        $this->checkStaticTemplateIsIncluded();
        parent::initializeAction();
    }

    /**
     * Calls the specified action method and passes the arguments.
     *
     * If the action returns a string, it is appended to the content in the
     * response object. If the action doesn't return anything and a valid
     * view exists, the view is rendered automatically.
     *
     * @return void
     * @api
     */
    protected function callActionMethod()
    {
        parent::callActionMethod();
        switch ($this->request->getFormat()) {
            case 'ics':
                header('Content-type: text/calendar; charset=utf-8');
                header('Content-Disposition: inline; filename=calendar.ics');
                echo $this->response->getContent();
                die();
                break;
        }
    }

    /**
     * Extend the view by the slot class and name and assign the variable to the view
     *
     * @param array  $variables
     * @param string $signalClassName
     * @param string $signalName
     */
    protected function slotExtendedAssignMultiple(array $variables, $signalClassName, $signalName)
    {
        // use this variable in your extension to add more custom variables
        $variables['extended'] = [];

        /** @var Dispatcher $dispatcher */
        $dispatcher = $this->objectManager->get('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
        $variables = $dispatcher->dispatch($signalClassName, $signalName, $variables);

        $this->view->assignMultiple($variables);
    }

    /**
     * Check if the static template is included
     */
    protected function checkStaticTemplateIsIncluded()
    {
        if (!isset($this->settings['dateFormat'])) {
            $this->addFlashMessage('Basic configuration settings are missing. It seems, that the Static Extension TypoScript is not loaded to your TypoScript configuration. Please add the calendarize TS to your TS settings.',
                'Configuration Error', FlashMessage::ERROR);
        }
    }
}