<?php

namespace App\Support;

class DarylTagResolver
{
    public function resolveName(int $tagId): ?string
    {
        $map = config('clients.daryl.etiquetas', []);
        return $map[$tagId] ?? null;
    }
}
