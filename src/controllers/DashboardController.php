<?php
/**
 * DashboardController — panel principal con estadísticas reales de SQLite.
 */

declare(strict_types=1);

final class DashboardController
{
    public function index(): void
    {
        requerir_login();
        $stats = DashboardModel::estadisticas();
        $actividades = DashboardModel::ultimasActividades(8);
        render_vista('dashboard/index', [
            'titulo_pagina' => 'Panel principal',
            'stats'         => $stats,
            'actividades'   => $actividades,
        ]);
    }
}
