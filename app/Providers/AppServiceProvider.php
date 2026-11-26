<?php

namespace App\Providers;

use App\Services\FcmPushGateway;
use App\Services\LogPushGateway;
use App\Services\PushGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // FCM الحقيقي إذا فيه ملف الاعتماد، وإلا نكتب الإشعار في السجل (للتطوير)
        $this->app->bind(PushGateway::class, function ($app) {
            $credentials = config('firebase.projects.app.credentials');

            if (is_string($credentials) && $credentials !== '' && file_exists($credentials)) {
                return $app->make(FcmPushGateway::class);
            }

            return $app->make(LogPushGateway::class);
        });
    }

    public function boot(): void
    {
        //
    }
}
