<?php

namespace Modules\Woocommerce\Http\Controllers;

use App\System;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class InstallController extends Controller
{
    protected $module_name;
    protected $appVersion;
    protected $module_display_name;

    public function __construct()
    {
        $this->module_name = 'woocommerce';
        $this->appVersion = config('woocommerce.module_version');
        $this->module_display_name = 'WooCommerce';
    }

    /**
     * Auto Install (no form, no license)
     */
    public function index()
    {
        return $this->autoInstall();
    }

    /**
     * Actual install logic
     */
    private function autoInstall()
    {
        try {
            DB::beginTransaction();

            ini_set('max_execution_time', 0);
            ini_set('memory_limit', '512M');

            $this->installSettings();

            // ❗ لو بدك يعيد التركيب حتى لو مركب، احذف هذا الشرط
            $is_installed = System::getProperty($this->module_name.'_version');
            if (!empty($is_installed)) {
                return redirect()->back()->with('status', [
                    'success' => true,
                    'msg' => 'WooCommerce already installed'
                ]);
            }

            DB::statement('SET default_storage_engine=INNODB;');

            Artisan::call('module:migrate', [
                'module' => 'Woocommerce',
                '--force' => true
            ]);

            System::addProperty(
                $this->module_name.'_version',
                $this->appVersion
            );

            DB::commit();

            return redirect()
                ->action([\App\Http\Controllers\Install\ModulesController::class, 'index'])
                ->with('status', [
                    'success' => true,
                    'msg' => 'WooCommerce installed successfully'
                ]);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::emergency(
                'File: '.$e->getFile().
                ' Line: '.$e->getLine().
                ' Message: '.$e->getMessage()
            );

            return back()->with('status', [
                'success' => false,
                'msg' => $e->getMessage()
            ]);
        }
    }

    /**
     * Install helpers
     */
    private function installSettings()
    {
        config(['app.debug' => true]);
        Artisan::call('config:clear');
    }

    /**
     * Uninstall
     */
    public function uninstall()
    {
        try {
            System::removeProperty($this->module_name.'_version');

            return back()->with('status', [
                'success' => true,
                'msg' => 'Uninstalled successfully'
            ]);
        } catch (\Exception $e) {
            return back()->with('status', [
                'success' => false,
                'msg' => $e->getMessage()
            ]);
        }
    }
}
