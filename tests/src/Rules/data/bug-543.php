<?php

namespace Bug543;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Code snippets from \Drupal\Tests\Core\Render\RendererTest.
 */
class TestClass {

    /**
     * Provides a list of both booleans.
     *
     * @return array
     */
    public function providerAccessValues() {
        return [
            [FALSE],
            [TRUE],
            [AccessResult::forbidden()],
            [AccessResult::allowed()],
            ['accessResultForbiddenNotInTrustedCallbacks'],
        ];
    }

    /**
     * Tests callbacks with the method names in a variable.
     *
     * @dataProvider providerAccessValues
     */
    public function testRenderWithAccessControllerResolved($access) {

        switch ($access) {
            case AccessResult::allowed():
                $method = 'accessResultAllowed';
                break;

            case AccessResult::forbidden():
                $method = 'accessResultForbidden';
                break;

            case FALSE:
                $method = 'accessFalse';
                break;

            case TRUE:
                $method = 'accessTrue';
                break;

            case 'accessResultForbiddenNotInTrustedCallbacks':
                $method = 'accessResultForbiddenNotInTrustedCallbacks';
                break;
        }

        $build = [
            '#access_callback' => TestAccessClass::class . '::' . $method,
        ];
    }

    /**
     * Tests callback with the actual method name.
     */
    public function bug543AccessResultAllowed(): void {
        $build = [
            '#access_callback' => TestAccessClass::class . '::accessResultAllowed',
        ];
    }

    /**
     * Tests callback with the actual method name.
     */
    public function bug543AccessResultForbidden(): void {
        $build = [
            '#access_callback' => TestAccessClass::class . '::accessResultForbidden',
        ];
    }

    /**
     * Tests callback with the actual method name.
     */
    public function bug543AccessFalse(): void {
        $build = [
            '#access_callback' => TestAccessClass::class . '::accessFalse',
        ];
    }

    /**
     * Tests callback with the actual method name.
     */
    public function bug543AccessTrue(): void {
        $build = [
            '#access_callback' => TestAccessClass::class . '::accessTrue',
        ];
    }

    /**
     * Tests callback with the actual method name.
     */
    public function bug543AccessResultForbiddenNotInTrustedCallbacks(): void {
        $build = [
            '#access_callback' => TestAccessClass::class . '::accessResultForbiddenNotInTrustedCallbacks',
        ];
    }

}

/**
 * Test class with callbacks.
 */
class TestAccessClass implements TrustedCallbackInterface {

    public static function accessTrue() {
        return TRUE;
    }

    public static function accessFalse() {
        return FALSE;
    }

    public static function accessResultAllowed() {
        return AccessResult::allowed();
    }

    public static function accessResultForbidden() {
        return AccessResult::forbidden();
    }

    public static function accessResultForbiddenNotInTrustedCallbacks() {
        return AccessResult::forbidden();
    }

    /**
     * {@inheritdoc}
     */
    public static function trustedCallbacks() {
        return ['accessTrue', 'accessFalse', 'accessResultAllowed', 'accessResultForbidden'];
    }

}
