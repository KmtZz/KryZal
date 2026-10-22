<?php
declare(strict_types=1);

function class_return(): string
{
    $page = in_array($_GET['page'] ?? '', ['classes', 'myclasses'], true)
        ? $_GET['page']
        : 'classes';
    $view = ($_GET['view'] ?? '') === 'calendar' ? 'calendar' : 'cards';
    $month = $_GET['month'] ?? date('Y-m');
    return $page .
        '&' .
        http_build_query([
            'view' => $view,
            'month' => preg_match('/^\d{4}-\d{2}$/', $month) ? $month : date('Y-m'),
            'q' => substr((string) ($_GET['q'] ?? ''), 0, 150),
            'state' => $_GET['state'] ?? '',
            'day' => ($_GET['day'] ?? '') === 'today' ? 'today' : '',
            'p' => max(1, (int) ($_GET['p'] ?? 1)),
        ]);
}
