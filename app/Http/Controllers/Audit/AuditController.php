<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user:id,name,lastname,middlename');

        if ($request->filled('table_name')) {
            $query->where('table_name', $request->table_name);
        }

        if ($request->filled('record_id')) {
            $query->where('record_id', $request->record_id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $logs = $query
            ->orderBy('created_at', 'desc')
            ->paginate(100);

        $logs->getCollection()->transform(function ($log) {

            $changes = [];

            if (
                $log->action === 'updated' &&
                is_array($log->old_values) &&
                is_array($log->new_values)
            ) {
                foreach ($log->new_values as $field => $newValue) {
                    $oldValue = $log->old_values[$field] ?? null;

                    // 🎯 FORMATEO CORRECTO PARA updated_at (UTC → Lima)
                    if ($field === 'updated_at') {
                        $oldValue = $oldValue
                            ? Carbon::parse($oldValue)
                            ->timezone('America/Lima')
                            ->format('d/m/Y H:i')
                            : null;

                        $newValue = $newValue
                            ? Carbon::parse($newValue)
                            ->timezone('America/Lima')
                            ->format('d/m/Y H:i')
                            : null;
                    }

                    if ($oldValue !== $newValue) {
                        $changes[] = [
                            'field'  => $field,
                            'before' => $oldValue,
                            'after'  => $newValue,
                        ];
                    }
                }
            }

            return [
                'id'         => $log->id,
                'table'      => $log->table_name,
                'record_id'  => $log->record_id,
                'action'     => $log->action,
                'user_id'    => $log->user_id,

                'user'       => $log->user ? [
                    'name'       => $log->user->name,
                    'lastname'   => $log->user->lastname,
                    'middlename' => $log->user->middlename,
                    'full_name'  => trim(
                        $log->user->name . ' ' .
                            $log->user->lastname . ' ' .
                            $log->user->middlename
                    ),
                ] : null,

                'created_at' => Carbon::parse($log->created_at)
                    ->timezone('America/Lima')
                    ->format('d/m/Y H:i'),

                'changes'    => $changes,
            ];
        });

        return response()->json($logs);
    }
}
