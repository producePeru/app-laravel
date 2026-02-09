<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    public function created(Model $model)
    {
        $this->log('created', $model);
    }

    public function updated(Model $model)
    {
        $this->log('updated', $model);
    }

    public function deleted(Model $model)
    {
        $this->log('deleted', $model);
    }

    protected function log($action, Model $model)
    {
        AuditLog::create([
            'table_name' => $model->getTable(),
            'record_id'  => $model->getKey(),
            'action'     => $action,
            'old_values' => $action === 'updated'
                ? $model->getOriginal()
                : null,
            'new_values' => $model->getChanges(),
            'user_id'    => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
