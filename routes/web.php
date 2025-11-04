<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/deploy', function (\Illuminate\Http\Request $request) {
    $secret = env('GITHUB_WEBHOOK_SECRET');
    $signature = $request->header('X-Hub-Signature-256');
    $payload = $request->getContent();
    $hash = 'sha256=' . hash_hmac('sha256', $payload, $secret);

    if (!hash_equals($hash, $signature)) {
        Log::warning('Invalid GitHub signature attempt');
        abort(403, 'Invalid signature.');
    }

    Log::info('GitHub Webhook triggered deployment...');

    try {
        // Pull latest code
        shell_exec('git pull origin main 2>&1');

        // Composer install
        shell_exec('composer install --no-interaction --prefer-dist --optimize-autoloader 2>&1');

        // Run migrations
        Artisan::call('migrate', ['--force' => true]);

        // Optimize
        Artisan::call('optimize:clear');
        Artisan::call('optimize');

        Log::info('Deployment complete!');
        return response()->json(['message' => 'Deployment successful!']);
    } catch (\Exception $e) {
        Log::error('Deployment failed: ' . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
});
