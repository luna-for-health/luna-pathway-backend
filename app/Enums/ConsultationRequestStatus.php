<?php

namespace App\Enums;

enum ConsultationRequestStatus: string
{
    /**
     * Just submitted, not yet looked at by staff. Default status on creation.
     */
    case New = 'new';

    /**
     * Staff has reached out and is awaiting a callback.
     */
    case Reminder = 'reminder';

    /**
     * Appointment confirmed.
     */
    case Validated = 'validated';

    /**
     * Already handled by staff.
     */
    case Treated = 'treated';
}
