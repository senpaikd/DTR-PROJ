<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case NotStarted = 'not_started';
    case ClockedIn = 'clocked_in';
    case Completed = 'completed';
    case MissingClockOut = 'missing_clock_out';
    case NoAttendanceRecorded = 'no_attendance_recorded';
    case CorrectionPending = 'correction_pending';
    case Corrected = 'corrected';
}
