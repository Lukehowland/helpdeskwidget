<?php

declare(strict_types=1);

namespace Lukehowland\HelpdeskWidget\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Comando de instalación del Widget de Helpdesk.
 * 
 * Este comando facilita la integración del widget en proyectos Laravel,
 * especialmente aquellos que usan AdminLTE v3.
 * 
 * Uso: php artisan helpdeskwidget:install
 */
class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'helpdeskwidget:install 
                            {--force : Sobrescribir archivos existentes}
                            {--skip-route : No agregar ruta a web.php}
                            {--skip-adminlte : No intentar agregar al sidebar de AdminLTE}';

    /**
     * The console command description.
     */
    protected $description = 'Instala el Widget de Helpdesk: publica config, crea vista y agrega ruta';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('');
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║          🎫 HELPDESK WIDGET - INSTALACIÓN                  ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->info('');

        // 1. Publicar configuración
        $this->publishConfig();

        // 2. Crear vista
        $this->createView();

        // 3. Agregar ruta
        if (!$this->option('skip-route')) {
            $this->addRoute();
        }

        // 4. Intentar agregar a AdminLTE sidebar
        if (!$this->option('skip-adminlte')) {
            $this->addToAdminLteSidebar();
        }

        // 5. Mostrar siguientes pasos
        $this->showNextSteps();

        return Command::SUCCESS;
    }

    /**
     * Publica el archivo de configuración.
     */
    protected function publishConfig(): void
    {
        $this->info('📦 Publicando configuración...');

        $this->call('vendor:publish', [
            '--tag' => 'helpdeskwidget-config',
            '--force' => $this->option('force'),
        ]);

        $this->info('   ✅ config/helpdeskwidget.php');
    }

    /**
     * Crea la vista Blade para el widget.
     */
    protected function createView(): void
    {
        $this->info('');
        $this->info('📄 Creando vista...');

        $viewPath = resource_path('views/helpdesk.blade.php');

        if (File::exists($viewPath) && !$this->option('force')) {
            $this->warn('   ⚠️  Vista ya existe: resources/views/helpdesk.blade.php');
            $this->warn('      Use --force para sobrescribir');
            return;
        }

        $viewContent = $this->getViewStub();
        File::put($viewPath, $viewContent);

        $this->info('   ✅ resources/views/helpdesk.blade.php');
    }

    /**
     * Agrega la ruta al archivo web.php
     */
    protected function addRoute(): void
    {
        $this->info('');
        $this->info('🛤️  Agregando ruta...');

        $routesPath = base_path('routes/web.php');
        $routeContent = File::get($routesPath);

        // Verificar si la ruta ya existe
        if (str_contains($routeContent, "Route::get('helpdesk'") || 
            str_contains($routeContent, 'Route::get("helpdesk"')) {
            $this->warn('   ⚠️  Ruta ya existe en routes/web.php');
            return;
        }

        // Agregar ruta al final del archivo
        $newRoute = <<<'ROUTE'


// ========== HELPDESK WIDGET ==========
// Ruta generada por: php artisan helpdeskwidget:install
Route::get('helpdesk', function () {
    return view('helpdesk');
})->name('helpdesk')->middleware('auth');

ROUTE;

        File::append($routesPath, $newRoute);

        $this->info('   ✅ Ruta agregada: GET /helpdesk');
    }

    /**
     * Intenta agregar el enlace al sidebar de AdminLTE.
     */
    protected function addToAdminLteSidebar(): void
    {
        $this->info('');
        $this->info('📋 Verificando AdminLTE...');

        $adminltePath = config_path('adminlte.php');

        if (!File::exists($adminltePath)) {
            $this->warn('   ⚠️  No se encontró config/adminlte.php');
            $this->line('      Puedes agregar manualmente al sidebar:');
            $this->line('');
            $this->line("      [");
            $this->line("          'text' => 'Centro de Soporte',");
            $this->line("          'url' => 'helpdesk',");
            $this->line("          'icon' => 'fas fa-fw fa-headset',");
            $this->line("      ],");
            return;
        }

        $adminlteContent = File::get($adminltePath);

        // Verificar si ya existe
        if (str_contains($adminlteContent, "'url' => 'helpdesk'") ||
            str_contains($adminlteContent, '"url" => "helpdesk"')) {
            $this->warn('   ⚠️  Enlace ya existe en AdminLTE');
            return;
        }

        $this->info('   ℹ️  Encontrado config/adminlte.php');
        $this->line('');
        $this->line('      Agrega esto manualmente al array "menu":');
        $this->line('');
        $this->comment("      ['header' => 'SOPORTE'],");
        $this->comment("      [");
        $this->comment("          'text' => 'Centro de Soporte',");
        $this->comment("          'url' => 'helpdesk',");
        $this->comment("          'icon' => 'fas fa-fw fa-headset',");
        $this->comment("      ],");
    }

    /**
     * Muestra los siguientes pasos para completar la configuración.
     */
    protected function showNextSteps(): void
    {
        $this->info('');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('');
        $this->info('✅ ¡Instalación completada!');
        $this->info('');
        $this->info('📌 SIGUIENTES PASOS:');
        $this->info('');
        $this->line('   1. Configura tu .env:');
        $this->comment('      HELPDESK_API_URL=https://proyecto-de-ultimo-minuto.online');
        $this->comment('      HELPDESK_API_KEY=tu-api-key-aqui');
        $this->info('');
        $this->line('   2. Limpia la caché:');
        $this->comment('      php artisan config:clear');
        $this->info('');
        $this->line('   3. Visita /helpdesk en tu navegador');
        $this->info('');
        $this->info('📖 Documentación: https://github.com/Lukehowland/helpdeskwidget');
        $this->info('');
    }

    /**
     * Retorna el contenido de la vista stub.
     */
    protected function getViewStub(): string
    {
        return <<<'BLADE'
{{--
    Vista del Widget de Helpdesk
    
    Generada automáticamente por: php artisan helpdeskwidget:install
    
    Personaliza esta vista según las necesidades de tu proyecto.
    Compatible con AdminLTE v3.
--}}
@extends('adminlte::page')

@section('title', 'Centro de Soporte')

@section('content_header')
    <h1><i class="fas fa-headset mr-2"></i>HelpDesk SaaS - Centro de Soporte</h1>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div id="helpdesk-widget-wrapper" style="width: 100%;">
                    <x-helpdesk-widget width="100%" />
                </div>
            </div>
        </div>
    </div>

    <style>
        #helpdesk-widget-wrapper iframe {
            width: 100% !important;
            border: none !important;
            display: block;
            min-height: 500px;
            transition: height 0.3s ease;
        }
    </style>

    <script>
        (function() {
            'use strict';

            console.log('🔍 [PARENT] Escuchando mensajes del widget');

            // Escuchar mensajes del iframe para redimensionar
            window.addEventListener('message', function(event) {
                if (event.data.type === 'widget-resize') {
                    const iframe = document.querySelector('#helpdesk-widget-wrapper iframe');
                    if (iframe) {
                        const newHeight = event.data.height;
                        console.log('📏 [PARENT] Recibido mensaje de resize:', newHeight);
                        iframe.style.height = newHeight + 'px';
                        console.log('✅ [PARENT] Altura actualizada a:', newHeight);
                    }
                }
            });

            console.log('✅ [PARENT] Listener de postMessage configurado');
        })();
    </script>
@endsection
BLADE;
    }
}
