<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Trait;

use Application\Controller\Trait\DobOver100WarningTrait;
use DateTime;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\View\Model\ViewModel;
use PHPUnit\Framework\TestCase;

class DobOver100WarningTraitTest extends TestCase
{
    use DobOver100WarningTrait;

    private function createTestRequest(array $postData = []): Request
    {
        $request = new Request();
        $request->setMethod('POST');
        $request->getPost()->fromArray($postData);
        return $request;
    }

    public function testOver100WithWarningAccepted(): void
    {
        $view = new ViewModel();
        $callbackCalled = false;
        $dob = (new DateTime('-101 years'))->format('Y-m-d');

        $request = $this->createTestRequest(['dob_warning_100_accepted' => '1']);

        $result = $this->handleDobOver100Warning(
            $dob,
            $request,
            $view,
            function () use (&$callbackCalled) {
                $callbackCalled = true;
            }
        );

        $this->assertTrue($result);
        $this->assertTrue($callbackCalled);
        $this->assertNull($view->getVariable('displaying_dob_100_warning'));
    }

    public function testOver100WithWarningNotAccepted(): void
    {
        $view = new ViewModel();
        $callbackCalled = false;
        $dob = (new DateTime('-101 years'))->format('Y-m-d');

        $request = $this->createTestRequest(); // No warning acceptance

        $result = $this->handleDobOver100Warning(
            $dob,
            $request,
            $view,
            function () use (&$callbackCalled) {
                $callbackCalled = true;
            }
        );

        $this->assertFalse($result);
        $this->assertFalse($callbackCalled);
        $this->assertTrue($view->getVariable('displaying_dob_100_warning'));
    }

    public function testUnder100NoWarningNeeded(): void
    {
        $view = new ViewModel();
        $callbackCalled = false;
        $dob = (new DateTime('-99 years'))->format('Y-m-d');

        $request = $this->createTestRequest();

        $result = $this->handleDobOver100Warning(
            $dob,
            $request,
            $view,
            function () use (&$callbackCalled) {
                $callbackCalled = true;
            }
        );

        $this->assertTrue($result);
        $this->assertTrue($callbackCalled);
        $this->assertNull($view->getVariable('displaying_dob_100_warning'));
    }

    public function testExactly100YearsWarningDisplayed(): void
    {
        $view = new ViewModel();
        $callbackCalled = false;
        $dob = (new DateTime('-100 years'))->format('Y-m-d');

        $request = $this->createTestRequest();

        $result = $this->handleDobOver100Warning(
            $dob,
            $request,
            $view,
            function () use (&$callbackCalled) {
                $callbackCalled = true;
            }
        );

        $this->assertFalse($result);
        $this->assertFalse($callbackCalled);
        $this->assertTrue($view->getVariable('displaying_dob_100_warning'));
    }
}
