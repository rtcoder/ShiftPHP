<?php

use Console\Commands\CreateCommand;
use Console\Commands\CreateController;
use Console\Commands\CreateDto;
use Console\Commands\CreateMiddleware;
use Console\Commands\CreateModel;
use Console\Commands\CreateModule;
use Console\Commands\CreateService;

return [
    'create:module scaffolds a module boundary' => function (): void {
        $modulesPath = makeTempModulesPath();

        try {
            (new CreateModule($modulesPath))->execute('billing');

            assertFileExists($modulesPath . '/Billing/Module.php', 'Module.php should be created.');
            assertFileExists($modulesPath . '/Billing/config.php', 'config.php should be created.');
            assertDirectoryExists($modulesPath . '/Billing/Commands', 'Commands directory should be created.');
            assertDirectoryExists($modulesPath . '/Billing/Controllers', 'Controllers directory should be created.');
            assertDirectoryExists($modulesPath . '/Billing/Services', 'Services directory should be created.');

            $module = file_get_contents($modulesPath . '/Billing/Module.php');
            $config = file_get_contents($modulesPath . '/Billing/config.php');
            assertStringContains('namespace Modules\\Billing;', $module, 'Module namespace should match module name.');
            assertStringContains("'namespace' => 'Modules\\\\Billing\\\\Commands\\\\'", $module, 'Module should expose command mappings.');
            assertStringContains("'module' => 'billing'", $config, 'Module config should use the module slug.');
        } finally {
            removeDirectory(dirname($modulesPath));
        }
    },
    'create:controller supports module option and inline module syntax' => function (): void {
        $modulesPath = makeTempModulesPath();

        try {
            (new CreateController($modulesPath))->execute('--module=billing', 'invoice');
            (new CreateController($modulesPath))->execute('billing:PaymentController');

            assertFileExists($modulesPath . '/Billing/Controllers/InvoiceController.php', 'Controller suffix should be added.');
            assertFileExists($modulesPath . '/Billing/Controllers/PaymentController.php', 'Inline module syntax should be supported.');

            $controller = file_get_contents($modulesPath . '/Billing/Controllers/InvoiceController.php');
            assertStringContains('namespace Modules\\Billing\\Controllers;', $controller, 'Controller namespace should match module.');
            assertStringContains('class InvoiceController extends Controller', $controller, 'Controller class should extend base controller.');
        } finally {
            removeDirectory(dirname($modulesPath));
        }
    },
    'create artifact commands write module-owned classes' => function (): void {
        $modulesPath = makeTempModulesPath();

        try {
            (new CreateModel($modulesPath))->execute('billing:Invoice');
            (new CreateService($modulesPath))->execute('billing:Invoice');
            (new CreateCommand($modulesPath))->execute('billing:SyncInvoices');
            (new CreateMiddleware($modulesPath))->execute('billing:Audit');
            (new CreateDto($modulesPath))->execute('billing:CreateInvoice');

            assertFileExists($modulesPath . '/Billing/Models/Invoice.php', 'Model should be created.');
            assertFileExists($modulesPath . '/Billing/Services/InvoiceService.php', 'Service suffix should be added.');
            assertFileExists($modulesPath . '/Billing/Commands/SyncInvoices.php', 'Command should be created.');
            assertFileExists($modulesPath . '/Billing/Middleware/AuditMiddleware.php', 'Middleware suffix should be added.');
            assertFileExists($modulesPath . '/Billing/Dto/CreateInvoiceDto.php', 'DTO suffix should be added.');

            $model = file_get_contents($modulesPath . '/Billing/Models/Invoice.php');
            $command = file_get_contents($modulesPath . '/Billing/Commands/SyncInvoices.php');
            assertStringContains('class Invoice extends Model', $model, 'Generated models should extend the base model.');
            assertStringContains("protected string \$table = 'invoices';", $model, 'Generated models should define a table name.');
            assertStringContains('return \'Usage: ./shift sync:invoices\';', $command, 'Generated command help should use CLI command syntax.');
        } finally {
            removeDirectory(dirname($modulesPath));
        }
    },
];
