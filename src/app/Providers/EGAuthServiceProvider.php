<?php

namespace Evergreen\Generic\App\Providers;

use Illuminate\Support\Facades\Gate;
use App\Providers\AuthServiceProvider;

class EGAuthServiceProvider extends AuthServiceProvider
{
    public function boot()
    {
        $this->registerEGLPolicies();
        parent::boot();
    }

    public function addPolicy($exclude, $identifier, $name)
    {
        if (!in_array($name, $exclude)) {
            Gate::define($name.'-'.$identifier, function ($user) use ($identifier, $name) {
                return $user->hasAccess([$name.'-'.$identifier]);
            });
        }
    }

    public function addPolicies($config)
    {
        foreach ($config as $name => $c) {
            if ($name == "config") {
                if (isset($c['permissions']) && ($c['permissions'] === true || (is_array($c['permissions']) && isset($c['permissions']['on']) && $c['permissions']['on']))) {
                    if (isset($c['identifier_path'])) {
                        $identifier = $c['identifier_path'];

                        $exclude = [];
                        if (is_array($c['permissions']) && isset($c['permissions']['exclude']) && is_array($c['permissions']['exclude'])) {
                            $exclude = $c['permissions']['exclude'];
                        }

                        $this->addPolicy($exclude, $identifier, 'create');
                        $this->addPolicy($exclude, $identifier, 'edit');
                        $this->addPolicy($exclude, $identifier, 'view');
                        $this->addPolicy($exclude, $identifier, 'delete');
                        $this->addPolicy($exclude, $identifier, 'permanentlyDelete');
                        $this->addPolicy($exclude, $identifier, 'restore');
                        $this->addPolicy($exclude, $identifier, 'list');
                    }

                    if (is_array($c['permissions']) && isset($c['permissions']['extra']) && is_array($c['permissions']['extra'])) {
                        foreach ($c['permissions']['extra'] as $extra) {
                            Gate::define($extra.'-'.$identifier, function ($user) use ($extra, $identifier) {
                                return $user->hasAccess([$extra.'-'.$identifier]);
                            });
                        }
                    }
                }
            } else {
                $this->addPolicies($c);
            }
        }
    }

    public function registerEGLPolicies()
    {
        $config = config("structure");
        if (!is_null($config)) {
            foreach ($config as $c) {
                $this->addPolicies($c);
            }
        }
    }
}
