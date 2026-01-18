<?php
namespace App\Observers;

use App\Models\History;

class HistoryObserver
{
    protected function save($model, $action, $old = null, $new = null)
    {
        History::create([
            'table_name' => $model->getTable(),
            'record_id'  => $model->getKey(),
            'action'     => $action,
            'old_data'   => $old ? json_encode($old) : null,
            'new_data'   => $new ? json_encode($new) : null,
            'user_id'    => auth()->id(),
            'ip_address' => request()->ip(),
        ]);
    }

    public function created($model)
    {
        $this->save($model, 'create', null, $model->toArray());
    }

    public function updated($model)
    {
        $this->save(
            $model,
            'update',
            $model->getOriginal(),
            $model->getChanges()
        );
    }

    public function deleted($model)
    {
        $this->save($model, 'delete', $model->toArray(), null);
    }
}
