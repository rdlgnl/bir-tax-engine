<?php

namespace App\Tax\Services;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A period the rule pack has no rules for. Shown as a page that isn't
 * there, rather than a tax computed under the wrong year's rules.
 */
class NoTaxRules extends NotFoundHttpException {}
