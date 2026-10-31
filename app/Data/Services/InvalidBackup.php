<?php

namespace App\Data\Services;

use RuntimeException;

/**
 * A backup that can't be restored, with a message for the taxpayer.
 */
class InvalidBackup extends RuntimeException {}
