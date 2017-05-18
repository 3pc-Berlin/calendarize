<?php
/**
 * Check if a date is upper
 *
 * @author  Tim Lochmüller
 */
namespace HDNET\Calendarize\Tests\Unit\ViewHelpers;

use HDNET\Calendarize\Tests\Unit\AbstractUnitTest;
use HDNET\Calendarize\ViewHelpers\IfDateUpperViewHelper;

/**
 * Check if a date is upper
 *
 * @author Tim Lochmüller
 */
class IfDateUpperViewHelperTest extends AbstractUnitTest
{

    /**
     * @test
     */
    public function testValidCheck()
    {
        $viewHelper = new IfDateUpperViewHelper();
        $this->assertEquals(true, $viewHelper->render(new \DateTime(), '23.04.2026'));
    }
}
