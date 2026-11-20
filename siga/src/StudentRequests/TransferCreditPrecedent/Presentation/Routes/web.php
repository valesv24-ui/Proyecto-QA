<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\StudentRequests\TransferCreditPrecedent\Presentation\Livewire\TransferCreditPrecedentComponent;

Route::middleware(['web', 'auth', 'verified'])
    ->get('transfer-credit-precedents', TransferCreditPrecedentComponent::class)
    ->name('studentrequests.transfercreditprecedent.index');
