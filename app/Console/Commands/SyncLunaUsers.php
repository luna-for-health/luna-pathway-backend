<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Luna\LunaUserService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('luna:sync-users')]
#[Description('Link every user that has no luna_user_id yet to its Luna account')]
class SyncLunaUsers extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(LunaUserService $lunaUserService): int
    {
        User::query()->whereNull('luna_user_id')->lazyById()->each(function (User $user) use ($lunaUserService): void {
            $lunaUserService->linkUser($user);
        });

        $remainingCount = User::query()->whereNull('luna_user_id')->count();

        $this->info($remainingCount.' user(s) still without a Luna account.');

        return $remainingCount === 0 ? self::SUCCESS : self::FAILURE;
    }
}
