<?php
/**
 * ReportController — reportes CSV y resumen de reportes.
 *
 * Permisos:
 *   - admin:   todos los reportes, incluida la actividad del sistema.
 *   - docente: calificaciones de sus cursos y estudiantes por carrera (agregado).
 */

declare(strict_types=1);

final class ReportController
{
    public function index(): void
    {
        requerir_rol(['admin', 'docente']);
        render_vista('reports/index', [
            'titulo_pagina'  => 'Reportes',
            'rol'            => rol_actual(),
            'resumenListo'   => is_file(ReportService::rutaResumen()),
        ]);
    }

    private function enviarArchivo(string $ruta, string $tipo): void
    {
        registrar_evento('REPORT_GEN', 'Descarga de reporte: ' . basename($ruta), 'OK');
        header('Content-Type: ' . $tipo);
        header('Content-Disposition: attachment; filename="' . basename($ruta) . '"');
        header('Content-Length: ' . filesize($ruta));
        readfile($ruta);
        exit;
    }

    public function estudiantes(): void
    {
        requerir_rol(['admin']);
        $this->enviarArchivo(ReportService::reporteEstudiantes(), 'text/csv; charset=UTF-8');
    }

    public function calificaciones(): void
    {
        requerir_rol(['admin', 'docente']);
        $teacherId = rol_actual() === 'docente' ? (int)id_usuario_actual() : null;
        $this->enviarArchivo(ReportService::reporteCalificaciones($teacherId), 'text/csv; charset=UTF-8');
    }

    public function porCarrera(): void
    {
        requerir_rol(['admin', 'docente']);
        $this->enviarArchivo(ReportService::reportePorCarrera(), 'text/csv; charset=UTF-8');
    }

    public function actividad(): void
    {
        requerir_rol(['admin']);
        $this->enviarArchivo(ReportService::reporteActividad(), 'text/csv; charset=UTF-8');
    }

    public function empaquetar(): void
    {
        requerir_rol(['admin', 'docente']);
        $etiqueta = ReportService::generarResumen((string)post('etiqueta', 'reporte'));
        registrar_evento('REPORT_PACKAGE', "Resumen de reportes generado: '{$etiqueta}'", 'OK');
        flash('ok', 'Resumen generado correctamente.');
        redirigir(url('reports'));
    }

    public function descargarResumen(): void
    {
        requerir_rol(['admin', 'docente']);
        $ruta = ReportService::rutaResumen();
        if (!is_file($ruta)) {
            flash('error', 'Aún no se ha generado ningún resumen.');
            redirigir(url('reports'));
        }
        $this->enviarArchivo($ruta, 'text/plain; charset=UTF-8');
    }
}
