<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SyncedDataCatalog;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SyncedDataController extends Controller
{
    private const PerPage = 15;

    public function index(Request $request, TenantContext $tenantContext, SyncedDataCatalog $catalog, ?string $category = null): Response
    {
        $categories = collect($catalog->categories());
        $activeCategory = $category ?? $categories->keys()->first();
        $definition = $catalog->category($activeCategory);
        $search = trim((string) $request->query('search', ''));

        abort_if(mb_strlen($search) > 100, 422);

        $query = $definition['model']::query()
            ->forCompany($tenantContext->company())
            ->with($activeCategory === 'people' ? ['emails:id,company_id,pipedrive_person_id,email,label,is_primary'] : [])
            ->latest('id');
        $catalog->applySearch($query, $search, $definition['search']);
        $paginator = $query->paginate(self::PerPage)->withQueryString();

        return Inertia::render('admin/synced-data/index', [
            'categories' => $categories->map(fn (array $item, string $slug): array => [
                'slug' => $slug,
                'label' => $item['label'],
                'description' => $item['description'],
                'count' => $item['model']::query()->forCompany($tenantContext->company())->count(),
            ])->values(),
            'activeCategory' => ['slug' => $activeCategory, 'label' => $definition['label'], 'description' => $definition['description']],
            'filters' => ['search' => $search],
            'records' => [
                'data' => collect($paginator->items())->map(fn ($record): array => $catalog->summary($record, $activeCategory))->values(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'readOnly' => true,
        ]);
    }

    public function show(TenantContext $tenantContext, SyncedDataCatalog $catalog, string $category, int $record): Response
    {
        $definition = $catalog->category($category);
        $model = $definition['model']::query()
            ->forCompany($tenantContext->company())
            ->with($definition['relations'])
            ->findOrFail($record);

        return Inertia::render('admin/synced-data/show', [
            'category' => ['slug' => $category, 'label' => $definition['label']],
            'record' => $catalog->detail($model, $category),
            'readOnly' => true,
        ]);
    }
}
