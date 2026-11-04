<?php

namespace App\Assistant\Exceptions;

use RuntimeException;

/**
 * The assistant couldn't answer: the provider refused the key, couldn't be
 * reached, or returned an error. The message is shown to the taxpayer.
 */
class AssistantUnavailable extends RuntimeException {}
