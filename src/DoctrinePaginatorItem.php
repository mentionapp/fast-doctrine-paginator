<?php

namespace Mention\FastDoctrinePaginator;

use Mention\FastDoctrinePaginator\Internal\CursorEncoder;
use Mention\Paginator\PaginatorItemInterface;

final class DoctrinePaginatorItem implements PaginatorItemInterface
{
    /** @var CursorEncoder */
    private $cursorEncoder;

    /** @var scalar[] */
    private $from;

    /** @var mixed */
    private $data;

    /**
     * @internal
     *
     * @param scalar[] $from
     * @param mixed    $data
     */
    public function __construct(CursorEncoder $cursorEncoder, array $from, $data)
    {
        $this->cursorEncoder = $cursorEncoder;
        $this->from = $from;
        $this->data = $data;
    }

    public function getCursor(): ?string
    {
        return $this->cursorEncoder->encode($this->from);
    }

    /** @return mixed */
    public function getData()
    {
        return $this->data;
    }
}
