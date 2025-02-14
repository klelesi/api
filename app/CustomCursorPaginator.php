<?php

namespace App;

use Illuminate\Pagination\CursorPaginator;

class CustomCursorPaginator extends CursorPaginator
{
    public function toArray()
    {
        return [
            'data' => $this->items->toArray(),
            'perPage' => $this->perPage(),
            'nextCursor' => $this->nextCursor() ? $this->nextCursor()->encode() : null,
            'prevCursor' => $this->previousCursor() ? $this->previousCursor()->encode() : null,
            'nextPageUrl' => $this->nextPageUrl() ? $this->nextPageUrl() : null,
            'prevPageUrl' => $this->previousPageUrl() ? $this->previousPageUrl() : null,
        ];
    }
}
