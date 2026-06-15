<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseService;
use Modules\Core\Models\Sequence;
use Illuminate\Http\Request;

class SequenceController extends Controller
{
    public function __construct(protected readonly BaseService $baseService) {}

    public function index()
    {
        return $this->erpExecution(function () {
            return $this->erpResponse(Sequence::query(), tags: ['sequence']);
        });
    }

    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $query = Sequence::where('sequence_id', $id);
            return $this->erpResponse($query, tags: ['sequence']);
        });
    }

    public function revise($id)
    {
        return $this->erpExecution(function () use ($id) {

            $this->baseService->executeProcedure('core.procedure_revise_sequence', [
                'sequence_id' => $id
            ]);

            return $this->erpResponse(
                message: "Sequence {$id} has been moved to drafts for revision."
            );
        }, "Failed to initiate revision for Sequence.");
    }

    public function destroy($id)
    {
        return $this->erpExecution(function () use ($id) {

            $this->baseService->requestDelete(Sequence::class, $id, 'core.procedure_revise_sequence');

            return $this->erpResponse(
                message: "Delete request for Sequence {$id} processed according to model policy."
            );

        }, "Failed to process delete request for Sequence.");
    }
}
