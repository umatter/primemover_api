<?php

namespace App\Modules\Job\Enum;

use Illuminate\Support\Collection;

enum JobType: string
{
    case visit = 'visitjob';
    case wait = 'waitjob';
    case tryEnterTextField = 'tryentertextfieldjob';
    case enterTextField = 'entertextfieldjob';
    case singleSelectUrl = 'singleselecturljob';
    case singleSelect = 'singleselectjob';
    case trySwitch = 'tryswitchjob';
    case switch = 'switchjob';
    case tryClick = 'tryclickjob';
    case click = 'clickjob';
    case scrollByDuration = 'scrollbydurationjob';
    case tryScrollByDuration = 'tryscrollbydurationjob';
    case scrollByLength = 'scrollbylengthjob';
    case tryScrollByLength = 'tryscrollbylengthjob';
    case handleAlert = 'handlealertjob';
    case tryHandleAlert = 'tryhandlealertjob';
    case lambdaSingleSelect = 'lambdasingleselectjob';

    public static function values(): array
    {
        return Collection::make(self::cases())
            ->map(fn (JobType $case) => $case->value)
            ->toArray();
    }
}
