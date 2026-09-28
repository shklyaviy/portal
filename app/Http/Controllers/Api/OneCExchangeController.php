<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SyncLog;
use App\Services\OneC\CatalogSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class OneCExchangeController extends Controller
{
    public function __invoke(Request $request, CatalogSyncService $sync): JsonResponse
    {
        $payload = $request->json()->all();

        if (! is_array($payload) || $payload === []) {
            return $this->error('Invalid JSON body', 400);
        }

        $validationError = $this->validateStructure($payload);
        if ($validationError !== null) {
            SyncLog::query()->create([
                'source' => '1c',
                'mode' => data_get($payload, 'sync.mode'),
                'batch_id' => data_get($payload, 'sync.batchId'),
                'status' => 'error',
                'message' => $validationError,
                'payload_hash' => hash('sha256', $request->getContent()),
            ]);

            return $this->error($validationError, 400);
        }

        try {
            $stats = $sync->upsertCatalog($payload);

            SyncLog::query()->create([
                'source' => '1c',
                'mode' => data_get($payload, 'sync.mode'),
                'batch_id' => data_get($payload, 'sync.batchId'),
                'status' => 'success',
                'message' => 'JSON catalog import',
                'stats' => $stats,
                'payload_hash' => hash('sha256', $request->getContent()),
            ]);

            return response()->json([
                'ok' => true,
                'stats' => $stats,
            ]);
        } catch (Throwable $e) {
            SyncLog::query()->create([
                'source' => '1c',
                'mode' => data_get($payload, 'sync.mode'),
                'batch_id' => data_get($payload, 'sync.batchId'),
                'status' => 'error',
                'message' => $e->getMessage(),
                'payload_hash' => hash('sha256', $request->getContent()),
            ]);

            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function validateStructure(array $payload): ?string
    {
        $hasCategories = isset($payload['categories']) && is_array($payload['categories']);
        $hasProducts = isset($payload['products']) && is_array($payload['products']);
        $hasPriceTypes = isset($payload['priceTypes']) && is_array($payload['priceTypes']);

        if (! $hasCategories && ! $hasProducts && ! $hasPriceTypes) {
            return 'Payload must include at least one of: categories, products, priceTypes';
        }

        foreach ($payload['categories'] ?? [] as $i => $cat) {
            if (! is_array($cat) || empty($cat['externalId']) || empty($cat['name'])) {
                return "categories[{$i}] requires externalId and name";
            }
        }

        foreach ($payload['products'] ?? [] as $i => $product) {
            if (! is_array($product) || empty($product['externalId']) || empty($product['name'])) {
                return "products[{$i}] requires externalId and name";
            }
        }

        foreach ($payload['priceTypes'] ?? [] as $i => $pt) {
            if (! is_array($pt) || empty($pt['externalId']) || empty($pt['name'])) {
                return "priceTypes[{$i}] requires externalId and name";
            }
        }

        return null;
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'error' => $message,
        ], $status);
    }
}
