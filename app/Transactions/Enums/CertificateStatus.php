<?php

namespace App\Transactions\Enums;

/**
 * Where a client's Form 2307 stands for one payment.
 */
enum CertificateStatus: string
{
    case NotNeeded = 'not_needed';
    case Received = 'received';
    case Pending = 'pending';
    case Missing = 'missing';
}
