<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /** Per-request cache so a single flash gets a stable id. */
    protected array $flashIds = [];

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user()
                    ? $request->user()->only(['id', 'emp_id', 'name', 'role', 'dept_id', 'dept_name', 'is_active', 'must_change_password'])
                    : null,
            ],
            'flash' => [
                'success' => fn () => $this->flash($request, 'success'),
                'error' => fn () => $this->flash($request, 'error'),
            ],
        ];
    }

    /**
     * Return the flash as an object with a unique id so the client can tell
     * every new flash apart — even when two consecutive actions produce the
     * exact same message text (e.g. editing two staff in a row).
     */
    protected function flash(Request $request, string $key): ?array
    {
        $message = $request->session()->get($key);
        if ($message === null) {
            return null;
        }

        if (!isset($this->flashIds[$key])) {
            $this->flashIds[$key] = Str::uuid()->toString();
        }

        return ['message' => $message, 'id' => $this->flashIds[$key]];
    }
}
