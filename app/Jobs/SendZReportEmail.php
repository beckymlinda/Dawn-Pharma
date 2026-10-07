<?php

namespace App\Jobs;

use App\Models\Register;
use App\Services\ZReportNotifier;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Sends the Z report once a register is closed. Dispatched "after response"
 * so the cashier is not kept waiting on the mail server, and no queue worker
 * is required.
 */
class SendZReportEmail
{
    use Dispatchable;

    public function __construct(public int $registerId, public ?int $triggeredBy = null)
    {
    }

    public function handle(ZReportNotifier $notifier): void
    {
        $register = Register::with(['user', 'location'])->find($this->registerId);

        if ($register && $register->closed_at && ZReportNotifier::isEnabled()) {
            $notifier->send($register, null, $this->triggeredBy);
        }
    }
}
