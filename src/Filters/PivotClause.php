<?php

namespace Mehradsadeghi\FilterQueryString\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pipeline\Pipeline;
use Mehradsadeghi\FilterQueryString\Filters\ComparisonClauses\Between\Between;
use Mehradsadeghi\FilterQueryString\Filters\ComparisonClauses\Between\NotBetween;
use Mehradsadeghi\FilterQueryString\Filters\ComparisonClauses\GreaterOrEqualTo;
use Mehradsadeghi\FilterQueryString\Filters\ComparisonClauses\GreaterThan;
use Mehradsadeghi\FilterQueryString\Filters\ComparisonClauses\LessOrEqualTo;
use Mehradsadeghi\FilterQueryString\Filters\ComparisonClauses\LessThan;

class PivotClause extends BaseClause
{
    private $availableFilters = [
        'default'          => WhereClause::class,
        'greater'          => GreaterThan::class,
        'greater_or_equal' => GreaterOrEqualTo::class,
        'less'             => LessThan::class,
        'less_or_equal'    => LessOrEqualTo::class,
        'between'          => Between::class,
        'not_between'      => NotBetween::class,
        'in'               => WhereInClause::class,
        'like'             => WhereLikeClause::class,
    ];

    public function apply($query): Builder
    {
        $normalized = $this->normalizeValues();

        foreach ($normalized as $table => $elements) {
            $query->whereHas(
                $table,
                function ($query) use ($elements) {
                    $availableFilter =
                        $this->availableFilters[$elements['filterName']] ?? $this->availableFilters['default'];
                    $pivot_query     = app(
                        $availableFilter,
                        ['filter' => $elements['filterName'], 'values' => $elements['value']]
                    );

                    return app(Pipeline::class)
                        ->send($query)
                        ->through($pivot_query)
                        ->thenReturn();
                }
            );
        }

        return $query;
    }

    public function validate($value): bool
    {
        if (is_null($value)) {
            return false;
        }

        foreach ((array) $value as $item) {
            $elements = separateCommaValues($item);
            if (count($elements) >= 3) {
                return false;
            }
            // TODO :: check it the related table exists
        }

        return true;
    }

    private function normalizeValues()
    {
        $normalized = [];

        foreach ((array) $this->values as $value) {
            [$table, $filterName, $value] = separateCommaValues($value, 3);
            $normalized[$table]['filterName'] = $filterName;
            $normalized[$table]['value'] = $value;
        }

        return $normalized;
    }
}
