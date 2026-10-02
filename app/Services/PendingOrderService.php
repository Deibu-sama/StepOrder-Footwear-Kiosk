<?php

namespace App\Services;

use Carbon\Carbon;

class PendingOrderService
{
    public function __construct(
        private readonly FirestoreService $firestore,
        private readonly ActivityLogService $activity,
    ) {}

    public function annotate(array $orders, int $warningHours = 24, int $expiryDays = 7): array
    {
        $now = Carbon::now(config('app.timezone'));

        foreach ($orders as &$order) {
            if (($order['status'] ?? 'pending') !== 'pending') {
                continue;
            }

            try {
                $created = Carbon::parse($order['created_at'] ?? null)->timezone(config('app.timezone'));
            } catch (\Throwable) {
                $order['_pending_age_hours'] = null;
                $order['_pending_stale'] = false;
                $order['_pending_expires_at'] = null;
                continue;
            }

            $ageMinutes = max(0, $created->diffInMinutes($now));
            $ageHours = intdiv($ageMinutes, 60);
            $expiryAt = $created->copy()->addDays($expiryDays);

            $order['_pending_age_hours'] = $ageHours;
            $order['_pending_age_label'] = $this->ageLabel($ageMinutes);
            $order['_pending_stale'] = $ageHours >= $warningHours;
            $order['_pending_expired'] = $now->greaterThanOrEqualTo($expiryAt);
            $order['_pending_expires_at'] = $expiryAt->toIso8601String();
        }

        unset($order);

        return $orders;
    }

    public function stale(array $orders, int $warningHours = 24): array
    {
        return array_values(array_filter(
            $this->annotate($orders, $warningHours),
            fn ($order) => ($order['status'] ?? 'pending') === 'pending'
                && !empty($order['_pending_stale'])
        ));
    }

    public function cancelExpired(int $expiryDays = 7): int
    {
        $now = Carbon::now(config('app.timezone'));
        $count = 0;

        foreach ($this->firestore->list('orders') as $order) {
            if (($order['status'] ?? 'pending') !== 'pending') {
                continue;
            }

            try {
                $created = Carbon::parse($order['created_at'] ?? null)->timezone(config('app.timezone'));
            } catch (\Throwable) {
                continue;
            }

            if ($created->diffInSeconds($now, false) < ($expiryDays * 86400)) {
                continue;
            }

            $id = $order['id'] ?? null;
            if (!$id) {
                continue;
            }

            $this->restoreStock($order);

            $cancelledAt = $now->toIso8601String();

            $this->firestore->update('orders', $id, [
                'status' => 'cancelled',
                'updated_at' => $cancelledAt,
                'cancelled_at' => $cancelledAt,
                'cancelled_by_email' => 'System',
                'cancelled_by_role' => 'system',
                'cancellation_reason' => 'Unpaid order expired after '.$expiryDays.' days.',
                'auto_cancelled' => true,
            ]);

            $items = $order['items'] ?? [];
            $unitCount = array_sum(array_map(
                fn ($item) => (int)($item['quantity'] ?? 0),
                $items
            ));

            $this->activity->record('ORDER_AUTO_CANCELLED', [
                'actor_email' => 'System',
                'actor_role' => 'system',
                'order_id' => $id,
                'order_number' => $order['order_number'] ?? $id,
                'items' => $items,
                'unit_count' => $unitCount,
                'total' => (float)($order['total'] ?? 0),
                'was_paid' => false,
                'auto_cancelled' => true,
                'details' => 'Unpaid order automatically cancelled after '.$expiryDays.' days; reserved stock restored.',
            ]);

            $count++;
        }

        return $count;
    }

    private function restoreStock(array $order): void
    {
        foreach (($order['items'] ?? []) as $item) {
            $product = $this->firestore->find('products', $item['product_id'] ?? '');

            if (!$product) {
                continue;
            }

            $variants = $product['variants'] ?? [];

            foreach ($variants as &$variant) {
                if (
                    ($variant['size'] ?? '') === ($item['size'] ?? '')
                    && ($variant['color'] ?? '') === ($item['color'] ?? '')
                ) {
                    $variant['stock'] = (int)($variant['stock'] ?? 0) + (int)($item['quantity'] ?? 0);
                }
            }

            unset($variant);

            $this->firestore->update('products', $product['id'], [
                'variants' => $variants,
            ]);
        }
    }

    private function ageLabel(int $minutes): string
    {
        if ($minutes < 60) {
            return max(1, $minutes).'m old';
        }

        $hours = intdiv($minutes, 60);
        if ($hours < 24) {
            return $hours.'h old';
        }

        $days = intdiv($hours, 24);
        $remainingHours = $hours % 24;

        return $remainingHours > 0
            ? $days.'d '.$remainingHours.'h old'
            : $days.'d old';
    }
}
