<?php

namespace App\Services;

use App\Models\SubscriptionPackage;
use Illuminate\Support\Collection;

class SubscriptionPackageService
{
    public function paidPackages(bool $visibleOnly = true): Collection
    {
        return SubscriptionPackage::query()
            ->active()
            ->when($visibleOnly, fn ($query) => $query->visible())
            ->where('is_paid', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function paidPlans(bool $visibleOnly = true): array
    {
        return collect($this->paidPackages($visibleOnly))
            ->mapWithKeys(fn (SubscriptionPackage $package): array => [
                $package->slug => $this->present($package),
            ])
            ->all();
    }

    public function present(SubscriptionPackage $package): array
    {
        return [
            'id' => $package->id,
            'slug' => $package->slug,
            'name' => $package->name,
            'description' => $package->description,
            'plan' => $package->plan,
            'price' => $package->price,
            'original_price' => $package->original_price,
            'currency' => $package->currency,
            'interval' => $package->billing_interval,
            'billing_interval' => $package->billing_interval,
            'devices' => $package->device_limit,
            'paddle_price_id' => $package->paddle_price_id,
        ];
    }
}
