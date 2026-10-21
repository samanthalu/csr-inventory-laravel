<?php

namespace App\Services;

use App\Models\FieldWorkSession;
use App\Models\HireItem;
use App\Models\RaAssetAssignment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Hands assets to research assistants. Shared by the assignment endpoints and by
 * "add RA with assets", so both enforce the same hire-pool rules.
 */
class RaAssetAssigner
{
    /**
     * Why these products can't be handed out in this session, or null if they can:
     * they must be out on the linked hire (when there is one) and not already held
     * by an RA in any session.
     */
    public function productError(FieldWorkSession $session, array $productIds): ?string
    {
        if ($session->fw_hire_id) {
            $onHire = HireItem::where('hire_id', $session->fw_hire_id)
                ->where('is_returned', false)
                ->whereIn('product_id', $productIds)
                ->pluck('product_id')->all();
            $missing = array_diff($productIds, $onHire);
            if ($missing) {
                return count($missing) . ' asset(s) are not out on this session\'s hire.';
            }
        }

        $held = RaAssetAssignment::whereNull('raa_date_returned')
            ->whereIn('raa_product_id', $productIds)
            ->with('product:prod_id,prod_tag_number')
            ->get();
        if ($held->isNotEmpty()) {
            $tags = $held->map(fn($a) => $a->product?->prod_tag_number ?? "#{$a->raa_product_id}")->take(5)->join(', ');
            return "Already assigned to an RA and not returned: {$tags}" . ($held->count() > 5 ? ' …' : '');
        }

        return null;
    }

    /**
     * Create one assignment per [raa_ra_id, raa_product_id] pair, all sharing the
     * same dates and condition. Callers validate first and wrap this in a transaction.
     */
    public function assign(int $sessionId, array $pairs, array $shared): Collection
    {
        $shared = self::normaliseDates($shared);
        $created = collect($pairs)->map(fn($p) => RaAssetAssignment::create([
            'raa_session_id'      => $sessionId,
            'raa_ra_id'           => $p['raa_ra_id'],
            'raa_product_id'      => $p['raa_product_id'],
            'raa_date_out'        => $shared['raa_date_out'],
            'raa_expected_return' => $shared['raa_expected_return'] ?? null,
            'raa_condition_out'   => $shared['raa_condition_out'],
        ]));

        $created->each->load('product:prod_id,prod_name,prod_tag_number');

        return $created->values();
    }

    /**
     * Reduce the assignment dates to Y-m-d. The `date` rule accepts full ISO
     * timestamps ("2026-10-23T00:00:00.000000Z"), which MySQL DATE columns reject.
     */
    public static function normaliseDates(array $data): array
    {
        foreach (['raa_date_out', 'raa_expected_return', 'raa_date_returned'] as $key) {
            if (!empty($data[$key])) {
                $data[$key] = Carbon::parse($data[$key])->toDateString();
            }
        }
        return $data;
    }
}
