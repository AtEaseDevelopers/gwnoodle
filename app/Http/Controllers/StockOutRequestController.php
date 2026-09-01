<?php

namespace App\Http\Controllers;

use App\DataTables\StockOutRequestDataTable;
use App\Models\StockOutRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StockOutRequestController extends AppBaseController
{
    /**
     * Display the list of batch stock-out approval requests.
     * Visible to anyone with warehouse access; actions are gated per role.
     */
    public function index(StockOutRequestDataTable $stockOutRequestDataTable)
    {
        return $stockOutRequestDataTable->render('stock_out_requests.index', [
            'pageTitle' => 'Stock-Out Approvals',
        ]);
    }

    /**
     * Approve a pending request and apply the stock-out. Admin only.
     */
    public function approve(Request $request, $id)
    {
        if (!$this->canApprove()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $stockOutRequest = StockOutRequest::find($id);

        if (empty($stockOutRequest)) {
            return response()->json(['success' => false, 'message' => 'Request not found'], 404);
        }

        if (!$stockOutRequest->isPending()) {
            return response()->json(['success' => false, 'message' => 'This request has already been reviewed'], 422);
        }

        DB::beginTransaction();

        try {
            $stockOutRequest->approveAndApply();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock-out request approved. ' . $stockOutRequest->quantity . ' units deducted.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error approving request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject a pending request with a remark. Admin only. No stock is applied.
     */
    public function reject(Request $request, $id)
    {
        if (!$this->canApprove()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $stockOutRequest = StockOutRequest::find($id);

        if (empty($stockOutRequest)) {
            return response()->json(['success' => false, 'message' => 'Request not found'], 404);
        }

        if (!$stockOutRequest->isPending()) {
            return response()->json(['success' => false, 'message' => 'This request has already been reviewed'], 422);
        }

        $validator = Validator::make($request->all(), [
            'approval_remark' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $stockOutRequest->status = StockOutRequest::STATUS_REJECTED;
        $stockOutRequest->approval_remark = $request->approval_remark;
        $stockOutRequest->reviewed_by = Auth::user()->name ?? 'system';
        $stockOutRequest->reviewed_at = now();
        $stockOutRequest->save();

        return response()->json([
            'success' => true,
            'message' => 'Stock-out request rejected.',
        ]);
    }

    /**
     * Approve several pending requests in one go. Admin only.
     * Each request is applied in its own transaction so one failure does not
     * roll back the rest; a summary of the outcome is returned.
     */
    public function bulkApprove(Request $request)
    {
        if (!$this->canApprove()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $ids = $this->validatedIds($request);

        if ($ids === null) {
            return response()->json(['success' => false, 'message' => 'No requests selected'], 422);
        }

        $approved = 0;
        $skipped = 0;
        $failed = 0;

        foreach (StockOutRequest::whereIn('id', $ids)->get() as $stockOutRequest) {
            if (!$stockOutRequest->isPending()) {
                $skipped++;
                continue;
            }

            DB::beginTransaction();

            try {
                $stockOutRequest->approveAndApply();
                DB::commit();
                $approved++;
            } catch (\Exception $e) {
                DB::rollBack();
                $failed++;
            }
        }

        return response()->json([
            'success' => $approved > 0,
            'message' => $this->bulkSummary('approved', $approved, $skipped, $failed),
            'approved' => $approved,
            'skipped' => $skipped,
            'failed' => $failed,
        ]);
    }

    /**
     * Reject several pending requests with a shared remark. Admin only.
     * No stock is applied.
     */
    public function bulkReject(Request $request)
    {
        if (!$this->canApprove()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'approval_remark' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $ids = $this->validatedIds($request);

        if ($ids === null) {
            return response()->json(['success' => false, 'message' => 'No requests selected'], 422);
        }

        $reviewer = Auth::user()->name ?? 'system';
        $rejected = 0;
        $skipped = 0;

        foreach (StockOutRequest::whereIn('id', $ids)->get() as $stockOutRequest) {
            if (!$stockOutRequest->isPending()) {
                $skipped++;
                continue;
            }

            $stockOutRequest->status = StockOutRequest::STATUS_REJECTED;
            $stockOutRequest->approval_remark = $request->approval_remark;
            $stockOutRequest->reviewed_by = $reviewer;
            $stockOutRequest->reviewed_at = now();
            $stockOutRequest->save();
            $rejected++;
        }

        return response()->json([
            'success' => $rejected > 0,
            'message' => $this->bulkSummary('rejected', $rejected, $skipped, 0),
            'rejected' => $rejected,
            'skipped' => $skipped,
        ]);
    }

    /**
     * Pull the selected request ids from the payload, returning null when none
     * are usable.
     *
     * @return int[]|null
     */
    protected function validatedIds(Request $request)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('ids', [])))));

        return empty($ids) ? null : $ids;
    }

    /**
     * Human-readable summary line for a bulk action outcome.
     */
    protected function bulkSummary($verb, $done, $skipped, $failed)
    {
        $parts = [$done . ' request(s) ' . $verb];

        if ($skipped > 0) {
            $parts[] = $skipped . ' skipped (already reviewed)';
        }

        if ($failed > 0) {
            $parts[] = $failed . ' failed (insufficient stock)';
        }

        return implode(', ', $parts) . '.';
    }

    /**
     * Edit the quantity of a pending request before approval.
     * Allowed for admin and Inventory Admin.
     */
    public function updateQty(Request $request, $id)
    {
        if (!$this->canEditQty()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $stockOutRequest = StockOutRequest::find($id);

        if (empty($stockOutRequest)) {
            return response()->json(['success' => false, 'message' => 'Request not found'], 404);
        }

        if (!$stockOutRequest->isPending()) {
            return response()->json(['success' => false, 'message' => 'Only pending requests can be edited'], 422);
        }

        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $stockOutRequest->quantity = (int) $request->quantity;
        $stockOutRequest->save();

        return response()->json([
            'success' => true,
            'message' => 'Quantity updated to ' . $stockOutRequest->quantity . '.',
            'quantity' => $stockOutRequest->quantity,
        ]);
    }

    /**
     * Admin can approve/reject.
     */
    protected function canApprove()
    {
        return Auth::check() && Auth::user()->hasRole('admin');
    }

    /**
     * Admin and Inventory Admin can edit quantity.
     */
    protected function canEditQty()
    {
        return Auth::check() && (Auth::user()->hasRole('admin') || Auth::user()->hasRole('Inventory Admin'));
    }
}
