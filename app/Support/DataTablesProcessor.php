<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

class DataTablesProcessor
{
    public function __construct(
        private readonly Request $request,
        private readonly Builder|Relation $query,
        private readonly array $config,
        private readonly \Closure $rowBuilder,
    ) {}

    public function respond(): array
    {
        $draw = $this->request->integer('draw');
        $start = max(0, $this->request->integer('start'));
        $lengthRaw = $this->request->integer('length', 10);
        $length = $lengthRaw < 0 ? 100 : max(1, min($lengthRaw, 100));

        $recordsTotal = (clone $this->query)->count();

        if (isset($this->config['filter']) && $this->config['filter'] instanceof \Closure) {
            ($this->config['filter'])($this->query, $this->request);
        }

        $search = trim((string) $this->request->string('search.value'));
        if ($search !== '' && ! empty($this->config['searchable'])) {
            $this->applySearch($search);
        }

        $recordsFiltered = (clone $this->query)->count();

        $this->applyOrder();

        $rows = $this->query->offset($start)->limit($length)->get();

        $data = [];
        $index = $start;
        foreach ($rows as $row) {
            $data[] = ($this->rowBuilder)($row, $index + 1);
            $index++;
        }

        return [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }

    private function applySearch(string $term): void
    {
        $this->query->where(function (Builder $query) use ($term) {
            foreach ($this->config['searchable'] as $column) {
                if (is_string($column)) {
                    $query->orWhere($column, 'like', "%{$term}%");
                } else {
                    $column($query, $term);
                }
            }
        });
    }

    private function applyOrder(): void
    {
        $order = $this->request->input('order.0');

        if (! is_array($order)) {
            return;
        }

        $columnIndex = (int) $order['column'];
        $dir = (($order['dir'] ?? 'asc') === 'desc') ? 'desc' : 'asc';

        if (isset($this->config['orderable'][$columnIndex])) {
            $this->query->orderBy($this->config['orderable'][$columnIndex], $dir);
        }
    }
}
