<?php

namespace DrupalUrl;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use function PHPStan\Testing\assertType;

assertType('string', (new Url('route_name'))->toString());
assertType('string', Url::fromRoute('route_name')->toString());
assertType('string', Url::fromRouteMatch(\Drupal::routeMatch())->toString());
assertType('string', Url::fromUri('the_uri')->toString());
assertType('string', Url::fromUserInput('user_input')->toString());
assertType('string', Url::createFromRequest(new Request())->toString());
assertType('string', (new Url('route_name'))->toString(FALSE));
assertType('string', Url::fromRoute('route_name')->toString(FALSE));
assertType('string', Url::fromRouteMatch(\Drupal::routeMatch())->toString(FALSE));
assertType('string', Url::fromUri('the_uri')->toString(FALSE));
assertType('string', Url::fromUserInput('user_input')->toString(FALSE));
assertType('string', Url::createFromRequest(new Request())->toString(FALSE));
assertType('Drupal\Core\GeneratedUrl', (new Url('route_name'))->toString(TRUE));
assertType('Drupal\Core\GeneratedUrl', Url::fromRoute('route_name')->toString(TRUE));
assertType('Drupal\Core\GeneratedUrl', Url::fromRouteMatch(\Drupal::routeMatch())->toString(TRUE));
assertType('Drupal\Core\GeneratedUrl', Url::fromUri('the_uri')->toString(TRUE));
assertType('Drupal\Core\GeneratedUrl', Url::fromUserInput('user_input')->toString(TRUE));
assertType('Drupal\Core\GeneratedUrl', Url::createFromRequest(new Request())->toString(TRUE));
