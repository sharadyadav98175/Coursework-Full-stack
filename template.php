<?php
declare(strict_types=1);

function render(string $tplPath, array $vars = []): void {
    extract($vars, EXTR_SKIP);
    include $tplPath;
}
