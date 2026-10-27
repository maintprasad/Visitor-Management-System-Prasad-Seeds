<?php

declare(strict_types=1);

namespace Vsm\Core;

interface Module
{
    /** @return array<string,Action> nama aksi (mis. CHECKIN_VISITOR) => definisi */
    public function actions(): array;
}
