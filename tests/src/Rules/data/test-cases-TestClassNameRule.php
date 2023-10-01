<?php

namespace TestCasesTestClassNameRule;

use Drupal\node\Entity\Node;
use Drupal\Tests\views\Functional\ViewTestBase;
use PHPUnit\Framework\TestCase;

class IgnoreNonExtendingClasses
{

}

abstract class IgnoreAbstractClasses extends TestCase
{

}

class IgnoreNonPHPUnitFrameworkTestCaseExtendingClasses extends Node
{

}

class CorrectlyNamedDirectlyExtendingPHPUnitFrameworkTestCaseTest extends TestCase
{

}

class IncorrectlyNamedDirectlyExtendingPHPUnitFrameworkTestCaseClass extends TestCase
{

}

class CorrectlyNamedIndirectlyExtendingPHPUnitFrameworkTestCaseTest extends ViewTestBase
{

}

class IncorrectlyNamedIndirectlyExtendingPHPUnitFrameworkTestCaseClass extends ViewTestBase
{

}
