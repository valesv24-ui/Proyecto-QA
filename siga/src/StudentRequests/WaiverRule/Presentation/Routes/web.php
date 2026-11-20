<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\StudentRequests\WaiverRule\Presentation\Livewire\WaiverRuleComponent;

Route::middleware(['web', 'auth', 'verified'])
    ->get('waiver-rules', WaiverRuleComponent::class)
    ->name('studentrequests.waiverrule.index');
