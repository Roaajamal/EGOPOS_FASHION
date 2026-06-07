<?php

namespace Modules\Accounting\Http\Controllers;

use App\System;
use Composer\Semver\Comparator;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class InstallController extends Controller
{
    public function __construct()
    {
        $this->module_name = 'accounting';
        $this->appVersion = config('accounting.module_version');
        $this->module_display_name = 'Accounting';
    }

    /**
     * Install
     *
     * @return Response
     */
    public function index()
    {

        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '512M');

        $this->installSettings();

        // التوجه مباشرة لعملية التثبيت دون عرض صفحة إدخال البيانات
        return $this->install();
    }

    /**
     * Initialize all install functions
     */
    private function installSettings()
    {
        config(['app.debug' => true]);
        Artisan::call('config:clear');
    }

    /**
     * Installing accounting Module
     */
    public function install()
    {
        try {
            // تم إزالة شروط التحقق من التراخيص هنا

            DB::beginTransaction();

            // تم إزالة دالة pos_boot التي تتحقق من الكود عبر السيرفر الخارجي

            $is_installed = System::getProperty($this->module_name.'_version');
            if (! empty($is_installed)) {
                // إذا كان مثبتاً بالفعل، نكتفي بالرسالة الناجحة
                $output = ['success' => 1, 'msg' => 'Accounting module is already installed'];
            } else {
                DB::statement('SET default_storage_engine=INNODB;');
                Artisan::call('module:migrate', ['module' => 'Accounting', '--force' => true]);
                Artisan::call('module:publish', ['module' => 'Accounting']);
                System::addProperty($this->module_name.'_version', $this->appVersion);

                DB::commit();

                $output = ['success' => 1,
                    'msg' => 'Accounting module installed successfully',
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = [
                'success' => false,
                'msg' => $e->getMessage(),
            ];
        }

        return redirect()
            ->action([\App\Http\Controllers\Install\ModulesController::class, 'index'])
            ->with('status', $output);
    }

    /**
     * Uninstall
     */
    public function uninstall()
    {

        try {
            System::removeProperty($this->module_name.'_version');

            $output = ['success' => true,
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            $output = ['success' => false,
                'msg' => $e->getMessage(),
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    /**
     * update module
     */
    public function update()
    {

        try {
            DB::beginTransaction();
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', '512M');

            $accounting_version = System::getProperty($this->module_name.'_version');

            if (Comparator::greaterThan($this->appVersion, $accounting_version)) {
                $this->installSettings();

                DB::statement('SET default_storage_engine=INNODB;');
                Artisan::call('module:migrate', ['module' => 'Accounting', '--force' => true]);
                Artisan::call('module:publish', ['module' => 'Accounting']);
                System::setProperty($this->module_name.'_version', $this->appVersion);
            } else {
                // حتى لو لم يكن هناك تحديث، لا نخرج بـ 404 لضمان استقرار الواجهة
                $output = ['success' => 1, 'msg' => 'Module is up to date.'];
                return redirect()->back()->with(['status' => $output]);
            }

            DB::commit();

            $output = ['success' => 1,
                'msg' => 'Accounting module updated Successfully to version '.$this->appVersion.' !!',
            ];

            return redirect()->back()->with(['status' => $output]);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with(['status' => ['success' => 0, 'msg' => $e->getMessage()]]);
        }
    }
}