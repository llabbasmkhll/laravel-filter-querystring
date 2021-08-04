<?php

namespace Mehradsadeghi\FilterQueryString\Filters;

use Illuminate\Database\Eloquent\Builder;

class PivotClause extends BaseClause
{

    public function apply($query): Builder
    {
        $normalized = $this->normalizeValues();

        foreach ($normalized as $table => $elements) {
            $query->whereHas(
                $table,
                function ($query) use ($elements) {
                    return $query->where($elements['field'], $elements['value']);
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
            if (count($elements) != 3) {
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
            [$table, $field, $value] = separateCommaValues($value);
            $normalized[$table]['field'] = $field;
            $normalized[$table]['value'] = $value;
        }

        return $normalized;
    }
}
