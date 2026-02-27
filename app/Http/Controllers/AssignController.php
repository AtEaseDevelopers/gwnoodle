<?php

namespace App\Http\Controllers;

use App\DataTables\AssignDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateAssignRequest;
use App\Http\Requests\UpdateAssignRequest;
use App\Repositories\AssignRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use App\Models\Assign;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;


class AssignController extends AppBaseController
{
    /** @var AssignRepository $assignRepository*/
    private $assignRepository;

    public function __construct(AssignRepository $assignRepo)
    {
        $this->assignRepository = $assignRepo;
    }

    /**
     * Display a listing of the Assign.
     *
     * @param AssignDataTable $assignDataTable
     *
     * @return Response
     */
    public function index(AssignDataTable $assignDataTable)
    {
        return $assignDataTable->render('assigns.index');
    }

    /**
     * Show the form for creating a new Assign.
     *
     * @return Response
     */
    public function create()
    {
        return view('assigns.create');
    }

    public function masscreate()
    {
        return view('assigns.masscreate');
    }

    /**
     * Store a newly created Assign in storage.
     *
     * @param CreateAssignRequest $request
     *
     * @return Response
     */
    public function store(CreateAssignRequest $request)
    {
        $input = $request->all();

        $assign = $this->assignRepository->create($input);

        Flash::success(__('assign.assign_saved_successfully'));

        return redirect(route('assigns.index'));
    }

    public function massstore(Request $request)
    {
        $input = $request->all();
        $successCount = 0;
        
        foreach ($input['customer'] as $index => $customerId) {
            $data = [
                'driver_id' => $input['driver_id'],
                'customer_id' => $customerId,
                'sequence' => $input['sequence'][$index] ?? 1,
            ];
            
            // Check if assignment already exists using where()->first()
            $existingAssignment = $this->assignRepository
                ->makeModel()
                ->where('driver_id', $data['driver_id'])
                ->where('customer_id', $data['customer_id'])
                ->first();
            
            if ($existingAssignment) {
                // Update existing assignment
                $updated = $this->assignRepository->update($data, $existingAssignment->id);
                if ($updated) {
                    $successCount++;
                }
            } else {
                // Create new assignment
                $assign = $this->assignRepository->create($data);
                if ($assign) {
                    $successCount++;
                }
            }
        }

        Flash::success($successCount . ' assignment(s) processed successfully.');
        return redirect(route('assigns.index'));
    }

    /**
     * Display the specified Assign.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $assign = $this->assignRepository->find($id);

        if (empty($assign)) {
            Flash::error(__('assign.assign_not_found'));

            return redirect(route('assigns.index'));
        }

        return view('assigns.show')->with('assign', $assign);
    }

    /**
     * Show the form for editing the specified Assign.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $assign = $this->assignRepository->find($id);

        if (empty($assign)) {
            Flash::error(__('assign.assign_not_found'));

            return redirect(route('assigns.index'));
        }

        return view('assigns.edit')->with('assign', $assign);
    }

    /**
     * Update the specified Assign in storage.
     *
     * @param int $id
     * @param UpdateAssignRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateAssignRequest $request)
    {
        $id = Crypt::decrypt($id);
        $assign = $this->assignRepository->find($id);

        if (empty($assign)) {
            Flash::error(__('assign.assign_not_found'));

            return redirect(route('assigns.index'));
        }

        $input = $request->all();

        $assign = $this->assignRepository->update($input, $id);

        Flash::success(__('assign.assign_updated_successfully'));

        return redirect(route('assigns.index'));
    }

    /**
     * Remove the specified Assign from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $assign = $this->assignRepository->find($id);

        if (empty($assign)) {
            Flash::error(__('assign.assign_not_found'));

            return redirect(route('assigns.index'));
        }

        $this->assignRepository->delete($id);

        Flash::success(__('assign.assign_deleted_successfully'));

        return redirect(route('assigns.index'));
    }

    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];

        $count = 0;

        foreach ($ids as $id) {

            $assign = $this->assignRepository->find($id);

            $count = $count + Assign::destroy($id);
        }

        return $count;
    }

    public function massupdatestatus(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        $status = $data['status'];

        $count = Assign::whereIn('id',$ids)->update(['status'=>$status]);

        return $count;
    }

    public function customerfindgroup(Request $request)
    {
        try {
            $data = $request->all();
            $group_id = $data['group_id'];
            $driver_id = $data['driver_id'];

            // Get all customers in the group
            $customers = Customer::where('group', 'like', '%' . $group_id . '%')
                ->select('id', 'company')
                ->get();

            // Get existing assignments for this driver
            $existingAssignments = Assign::where('driver_id', $driver_id)
                ->whereIn('customer_id', $customers->pluck('id'))
                ->orderBy('sequence', 'asc')
                ->get()
                ->keyBy('customer_id');

            $result = [];

            if ($existingAssignments->isNotEmpty()) {
                // Use existing assignments with their sequences
                foreach ($existingAssignments as $assignment) {
                    $customer = $customers->firstWhere('id', $assignment->customer_id);
                    if ($customer) {
                        $result[] = [
                            'id' => $customer->id,
                            'company' => $customer->company,
                            'sequence' => $assignment->sequence
                        ];
                    }
                }
                
                // Add remaining customers (not yet assigned) with new sequences
                $maxSequence = $existingAssignments->max('sequence') ?: 0;
                $remainingCustomers = $customers->whereNotIn('id', $existingAssignments->pluck('customer_id'));
                
                foreach ($remainingCustomers as $index => $customer) {
                    $result[] = [
                        'id' => $customer->id,
                        'company' => $customer->company,
                        'sequence' => $maxSequence + $index + 1
                    ];
                }
            } else {
                // No existing assignments, just list all customers with default sequence
                foreach ($customers as $index => $customer) {
                    $result[] = [
                        'id' => $customer->id,
                        'company' => $customer->company,
                        'sequence' => $index + 1
                    ];
                }
            }

            // Sort by sequence
            usort($result, function($a, $b) {
                return $a['sequence'] - $b['sequence'];
            });

            if (empty($result)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No customers found in this group'
                ], 200);
            }

            return response()->json([
                'status' => true,
                'message' => 'OK',
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => "Something went wrong: " . $e->getMessage()
            ], 400);
        }
    }
}
