<?php

namespace TestCasesTestClassesProtectedPropertyModulesRule;

use Drupal\node\Entity\Node;
use Drupal\Tests\node\Functional\NodeAccessFieldTest;

class ProtectedStaticPropertyModulesClass extends NodeAccessFieldTest {

    /**
     * Modules to install.
     *
     * @var array
     */
    protected static $modules = ['action', 'user'];
}

class PublicStaticPropertyModulesClass extends NodeAccessFieldTest {

    /**
     * Modules to install.
     *
     * @var array
     */
    public static $modules = ['action', 'user'];
}

class ProtectedStaticPropertyNotModulesClass extends NodeAccessFieldTest {

    /**
     * Modules to install.
     *
     * @var array
     */
    protected static $not_modules = ['action', 'user'];
}

class PublicStaticPropertyNotModulesClass extends NodeAccessFieldTest {

    /**
     * Modules to install.
     *
     * @var array
     */
    public static $not_modules = ['action', 'user'];
}

class NonExtendingProtectedStaticPropertyModulesClass {

    /**
     * Modules to install.
     *
     * @var array
     */
    protected static $modules = ['action', 'user'];
}

class NonExtendingPublicStaticPropertyModulesClass {

    /**
     * Modules to install.
     *
     * @var array
     */
    public static $modules = ['action', 'user'];
}

class NonExtendingPHPUnitFrameworkTestCaseProtectedStaticPropertyModulesClass extends Node {

    /**
     * Modules to install.
     *
     * @var array
     */
    protected static $modules = ['action', 'user'];
}

class NonExtendingPHPUnitFrameworkTestCasePublicStaticPropertyModulesClass extends Node {

    /**
     * Modules to install.
     *
     * @var array
     */
    public static $modules = ['action', 'user'];
}
