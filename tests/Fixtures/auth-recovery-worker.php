<?php

use App\Models\PasswordResetRequest;
use App\Models\User;
use App\Services\Auth\AccountAccess;
use App\Services\Auth\PasswordRecovery;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\HttpKernel\Exception\HttpException;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$connection = config('database.connections.'.config('database.default'));
if (! $app->environment('testing') || ($connection['database'] ?? null) !== 'db_elingap_testing') {
    fwrite(STDERR, 'An isolated test database is required.');
    exit(1);
}
$input = json_decode(fgets(STDIN), true, flags: JSON_THROW_ON_ERROR);
file_put_contents($input['ready'], 'ready');
echo "READY\n";
fflush(STDOUT);
$deadline = microtime(true) + 10;
while (! is_file($input['barrier'])) {
    if (microtime(true) >= $deadline) {
        fwrite(STDERR, 'Recovery test barrier timed out.');
        exit(1);
    }
    clearstatcache(true, $input['barrier']);
    usleep(10000);
}
try {
    $service = $app->make(PasswordRecovery::class);
    if ($input['action'] === 'request') {
        $result = $service->request($input['username'])?->id;
    } elseif ($input['action'] === 'complete') {
        $result = $service->complete($input['username'], $input['code'], $input['password']);
    } elseif ($input['action'] === 'deactivate') {
        $app->make(AccountAccess::class)->setStatus(User::findOrFail($input['admin']), User::findOrFail($input['user']), false);
        $result = 'deactivated';
    } else {
        $service->review(PasswordResetRequest::findOrFail($input['request']), User::findOrFail($input['admin']), 'approve', true, $input['password']);
        $result = 'approved';
    }
    echo json_encode(['result' => $result]);
} catch (HttpException $exception) {
    if (in_array($exception->getStatusCode(), [409, 422], true)) {
        echo json_encode(['result' => 'ineligible']);
    } else {
        echo json_encode(['error' => 'Recovery worker refused.']);
        exit(1);
    }
} catch (Throwable) {
    echo json_encode(['error' => 'Recovery worker failed.']);
    exit(1);
}
